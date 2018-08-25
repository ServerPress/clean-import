<?php

/**
 * Performs database cleanup after site is imported into DesktopServer
 */

class DS_Clean_Import_Database extends DS_Clean_Import_Base
{
	private $_db_user = NULL;
	private $_db_pass = NULL;
	private $_prefix = NULL;
	private $_dbc = NULL;

	private $options = NULL;

	/**
	 * Perform cleanup on the database
	 * @param string $db_user The database user name
	 * @param string $db_password The database password
	 * @param string $db_prefix The database table prefix
	 */
	public function clean_import( $db_user, $db_password, $db_prefix )
	{
DS_Clean_Import::debug(__METHOD__.'()');
		$this->_db_user = $db_user;
		$this->_db_pass = $db_password;
		$this->_prefix = $db_prefix;

		$this->create_connection();
		$this->load_options();

		$this->show_counts( 'before' );

		$this->clean_transients();
		if ( '1' === $this->options['revisions'] )
			$this->remove_revisions();
		if ( '1' === $this->options['trashed'] )
			$this->remove_trashed();
		if ( '1' === $this->options['comments'] )
			$this->remove_comments();
		if ( '1' === $this->options['postmeta'] )
			$this->remove_postmeta();
		if ( '1' === $this->options['usermeta'] )
			$this->remove_usermeta();

		$this->show_counts( 'after' );
	}

	/**
	 * Creates a connection to the newly Imported database using the WordPress wpdb class.
	 */
	private function create_connection()
	{
DS_Clean_Import::debug(__METHOD__.'()');
		if ( function_exists( 'ds_trace_error_handler' ) )
			set_error_handler( 'ds_trace_error_handler', E_ALL );
		if ( function_exists( 'ds_trace_exception_handler' ) )
			set_exception_handler( 'ds_trace_exception_handler' );

DS_Clean_Import::debug(__METHOD__.'() install=' . $this->install_path . ' site=' . $this->site_path);

DS_Clean_Import::debug(__METHOD__.'() user=' . $this->_db_user . ' pass=' . $this->_db_pass);

DS_Clean_Import::debug(__METHOD__.'() prefix=' . $this->_prefix);

		$file = $this->install_path . 'wp-includes/wp-db.php';
DS_Clean_Import::debug(__METHOD__.'() requiring=' . $file);
		require_once( $file );
DS_Clean_Import::debug(__METHOD__.'() constant');

		// these are required by the wpdb() constructor and error handling methods
		if ( ! defined( 'WP_DEBUG_DISPLAY' ) )
			define( 'WP_DEBUG_DISPLAY', FALSE );
		if ( ! defined( 'WP_CONTENT_DIR' ) )
			define( 'WP_CONTENT_DIR', $this->install_path . 'wp-content' );
		if ( ! defined( 'ABSPATH' ) )
			define( 'ABSPATH', $this->install_path );

DS_Clean_Import::debug(__METHOD__.'() instantiating...');
		$this->_dbc = new wpdb( $this->_db_user, $this->_db_pass, $this->_db_user, 'localhost' );
DS_Clean_Import::debug(__METHOD__.'() set_prefix(' . $this->_prefix . ')');
		$this->_dbc->set_prefix( $this->_prefix );
		$this->_dbc->show_errors( FALSE );
	}

	/**
	 * Shows the counts of various tables to show that the clean up is working
	 * @param string $type The type of report. One of 'before' or 'after'
	 */
	private function show_counts( $type )
	{
		$tables = array(
			$this->_prefix . 'posts',
			$this->_prefix . 'postmeta',
			$this->_prefix . 'comments',
			$this->_prefix . 'commentmeta',
			$this->_prefix . 'options',
		);
DS_Clean_Import::debug(__METHOD__ . "('{$type}')");
		foreach ( $tables as $table ) {
			$sql = "SELECT COUNT(*) FROM `{$table}`";
			$res = $this->_dbc->get_col( $sql );
			$count = empty($res) ? 0 : $res[0];
DS_Clean_Import::debug(" - there are {$count} records in the `{$table}` table");
		}
	}

	/**
	 * Loads the options via the DS_Clean_Import_Options class
	 */
	private function load_options()
	{
		require_once( dirname( __FILE__ ) . '/options.php' );
		$options = new DS_Clean_Import_Options();
		$this->options = $options->get_options();
	}

	/**
	 * Cleans the transients, based on the selected behavior
	 */
	private function clean_transients()
	{
DS_Clean_Import::debug(__METHOD__.'(): ' . $this->options['transients']);
		switch ( $this->options['transients'] ) {
		case 'nothing':
			break;
		case 'delete':
			// delete all transients, regarless of the expiration time
			$sql = "DELETE FROM `{$this->_prefix}options`
					WHERE `option_name` LIKE '_transient_%' OR
							`option_name` LIKE '_site_transient_%' ";
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
			$this->_dbc->query( $sql );
			break;

		case 'expire':
			// get timestamp of the database.sql file
DS_Clean_Import::debug(__METHOD__.'() searching for database files');
			$files = glob( $this->site_path . 'database*.sql' );
DS_Clean_Import::debug(__METHOD__.'() found: ' . var_export($files, TRUE));
			$expire = time();
			foreach ( $files as $filename ) {
				$time = filemtime( $filename );
				if ( $time < $expire )
					$expire = $time;
			}

			// build a list of transients that expired before the database was exported
DS_Clean_Import::debug(__METHOD__.'() expire time=' . $expire);
			$sql = "SELECT REPLACE('option_name', 'timeout_', '') AS `trans_name`
					FROM `{$this->_prefix}options`
					WHERE (`option_name` LIKE '_transient_timeout_%' OR
							`option_name` LIKE '_site_transient_timeout_%' ) AND
					`option_value` < {$expire} ";
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
			$res = $this->_dbc->get_results( $sql, ARRAY_A );

			$keys = array();
			foreach ( $res as $id => $row )
				$keys[] = $row['trans_name'];
			// add the timeout keys
			$add_keys = array();
			foreach ( $keys as $key )
				$add_keys[] = str_replace( '_transient_', '_transient_timeout_', $key );
			$all_keys = array_merge( $keys, $add_keys );

			// remove all keys in the list
			$sql = "DELETE
					FROM `{$this->_prefix}options`
					WHERE `option_name` IN ('" . implode( '\',\'', $all_keys ) . "')";
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
			$this->_dbc->query( $sql );
			// fall through to allow updating any remaining transients

		case 'update':
			// update all transient expiration times to be NOW() + 1 day
			$sql = "UPDATE `{$this->_prefix}options`
						SET `option_value`=" . ( time() + 86400 ) .			// set to 24 hours from now
						" WHERE `option_name` LIKE '_transient_timeout_%' OR
								`option_name` LIKE '_site_transient_timeout_%'";
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
			$this->_dbc->query( $sql );
			break;
		}
DS_Clean_Import::debug(__METHOD__.'() completed');
	}

	/**
	 * Removes post revisions and any associated meta data
	 */
	private function remove_revisions()
	{
DS_Clean_Import::debug(__METHOD__.'()');
		// find all the IDs for the post revisions
		$sql = "SELECT `ID`
				FROM `{$this->_prefix}posts`
				WHERE `post_type`='revision' ";
		$ids = $this->_dbc->get_col( $sql );

		if ( count( $ids ) ) {
			// remove the posts
			$sql = "DELETE
					FROM `{$this->_prefix}posts`
					WHERE `ID` IN (" . implode( ',', $ids ) . ')';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
			$this->_dbc->query( $sql );

			// and remove the postmeta
			$sql = "DELETE
					FROM `{$this->_prefix}postmeta`
					WHERE `post_id` IN (" . implode( ',', $ids ) . ')';
			$this->_dbc->query( $sql );
		} else {
DS_Clean_Import::debug(__METHOD__.'() no revisions found');
		}
DS_Clean_Import::debug(__METHOD__.'() completed');
	}

	/**
	 * Removes and Trashed posts and any associated meta data
	 */
	private function remove_trashed()
	{
DS_Clean_Import::debug(__METHOD__.'()');
		// find all the trashed posts
		$sql = "SELECT `ID`
				FROM `{$this->_prefix}posts`
				WHERE `post_status`='trash'";
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
		$ids = $this->_dbc->get_col( $sql );

		if ( count( $ids ) ) {
			// remove the posts
			$sql = "DELETE
					FROM `{$this->_prefix}posts`
					WHERE `ID` IN (" . implode( ',', $ids ) . ')';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
			$this->_dbc->query( $sql );

			// and remove the postmeta
			$sql = "DELETE
					FROM `{$this->_prefix}postmeta`
					WHERE `post_id` IN (" . implode( ',', $ids ) . ')';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
			$this->_dbc->query( $sql );
		} else {
DS_Clean_Import::debug(__METHOD__.'() no trashed posts found');
		}
DS_Clean_Import::debug(__METHOD__.'() completed');
	}

	/**
	 * Remove any trashed Comments and any associated meta data
	 */
	private function remove_comments()
	{
DS_Clean_Import::debug(__METHOD__.'()');
		$sql = "SELECT `comment_ID`
				FROM `{$this->_prefix}comments`
				WHERE `comment_type` = 'trash' ";
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
		$ids = $this->_dbc->get_col( $sql );

		if ( count( $ids ) ) {
			// remove the comments
			$sql = "DELETE
					FROM `{$this->_prefix}comments`
					WHERE `comment_ID` IN (" . implode( ',', $ids ) . ')';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
			$this->_dbc->query( $sql );

			// and remove any comment meta
			$sql = "DELETE
					FROM `{$this->_prefix}commentmeta`
					WHERE `comment_id` IN (" . implode( ',', $ids ) . ')';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
			$this->_dbc->query( $sql );
		} else {
DS_Clean_Import::debug(__METHOD__.'() no comments found');
		}
DS_Clean_Import::debug(__METHOD__.'() completed');
	}

	/**
	 * Remove any orphaned postmeta
	 */
	private function remove_postmeta()
	{
DS_Clean_Import::debug(__METHOD__.'()');
		$sql = "DELETE `{$this->_prefix}postmeta`
				FROM `{$this->_prefix}postmeta`
				LEFT JOIN `{$this->_prefix}posts` ON `{$this->_prefix}posts`.`ID` = `{$this->_prefix}postmeta`.`post_id`
				WHERE `{$this->_prefix}posts`.`ID` IS NULL";
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
		$this->_dbc->query( $sql );
DS_Clean_Import::debug(__METHOD__.'() completed');
	}

	/**
	 * Remove any orphaned usermeta
	 */
	private function remove_usermeta()
	{
DS_Clean_Import::debug(__METHOD__.'()');
		$sql = "DELETE `{$this->_prefix}usermeta`
				FROM `{$this->_prefix}usermeta`
				LEFT JOIN `{$this->_prefix}users` ON `{$this->_prefix}users`.`ID` = `{$this->_prefix}usermeta`.`user_id`
				WHERE `{$this->_prefix}users`.`ID` IS NULL";
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' sql=' . $sql);
		$this->_dbc->query( $sql );
DS_Clean_Import::debug(__METHOD__.'() completed');
	}
}

// these are needed because WPDB() uses filters, MultiSite and translation functions and we're using it in a context where WP is not initialized

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $name, $value )
	{
		return $value;
	}
}

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite( )
	{
		return FALSE;
	}
}

if ( ! function_exists( 'wp_load_translations_early' ) ) {
	function wp_load_translations_early()
	{
		return FALSE;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $val, $domain = '' )
	{
		return $val;
	}
}

if ( ! function_exists( 'wp_debug_backtrace_summary' ) ) {
	function wp_debug_backtrace_summary( $ignore_class = NULL, $skip_frames = 0, $pretty = TRUE )
	{
		if ( version_compare( PHP_VERSION, '5.2.5', '>=' ) )
			$trace = debug_backtrace( false );
		else
			$trace = debug_backtrace();

		$caller = array();
		$check_class = ! is_null( $ignore_class );
		$skip_frames++; // skip this function

		foreach ( $trace as $call ) {
			if ( $skip_frames > 0 ) {
				$skip_frames--;
			} else if ( isset( $call['class'] ) ) {
				if ( $check_class && $ignore_class === $call['class'] )
					continue; // Filter out calls

				$caller[] = "{$call['class']}{$call['type']}{$call['function']}";
			} else {
				if ( in_array( $call['function'], array( 'do_action', 'apply_filters' ) ) ) {
					$caller[] = "{$call['function']}('{$call['args'][0]}')";
				} elseif ( in_array( $call['function'], array( 'include', 'include_once', 'require', 'require_once' ) ) ) {
					$caller[] = $call['function'] . "('" . str_replace( array( WP_CONTENT_DIR, ABSPATH ) , '', $call['args'][0] ) . "')";
				} else {
					$caller[] = $call['function'];
				}
			}
		}
		if ( $pretty )
			return join( ', ', array_reverse( $caller ) );
		else
			return $caller;	
	}
}