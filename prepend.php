<?php

/** 
 * Delcare Clean Import Classes
 */
if ( ! class_exists( 'DS_CLEAN_IMPORT', FALSE ) ) {
	class DS_Clean_Import
	{
		const DEBUG_LOG = FALSE;						// set to TRUE to enable logging
		const DB_WORK_FILE = 'ds-dbwork.tmp';		// temporary contents of database.sql for $table_prefix detection

		public static $instance = NULL;

		public $site_name = NULL;
		public $site_path = NULL;
		public $install_path = NULL;
		public $backup_ext = NULL;

		public $config_file = NULL;						// DS_Config_File instance for wp-config.php

		private static $_log_file = NULL;
		private static $_log = FALSE;

		public function __construct()
		{
			self::$instance = $this;
			require_once  __DIR__ . '/inc/base.php';
		}

		/**
		 * Processor for 'site_preimport' events
		 */
		public function pre_import_process()
		{
			global $ds_runtime;
self::debug(__METHOD__.'(): ui event=' . var_export($ds_runtime->last_ui_event, TRUE));

			// ensure trailing directory separator for both platforms #34
			if ( isset( $ds_runtime->last_ui_event->info[2] ) )
				$ds_runtime->last_ui_event->info[2] = rtrim( $ds_runtime->last_ui_event->info[2], '\\/' ) . DIRECTORY_SEPARATOR;

			// first, any processors to move files into the expected place
			$duplicator = $this->load_class( 'Duplicator' );
			$duplicator->pre_import_process( $ds_runtime->last_ui_event->info );

			// second, any updates to the .sql files
			$aria = $this->load_class( 'AriaEngine' );
			$aria->pre_import_process( $ds_runtime->last_ui_event->info );
			$logblock = $this->load_class( 'LogBlock' );
			$logblock->pre_import_process( $ds_runtime->last_ui_event->info );

			// copy the database.sql file to a temp file so we can check it during 'site_imported'
			$dbwork_src = $ds_runtime->last_ui_event->info[1] . 'database.sql';
			$dbwork_dst = $ds_runtime->last_ui_event->info[1] . self::DB_WORK_FILE;
			if ( ! file_exists( $dbwork_dst ) && file_exists( $dbwork_src ) ) {
				// copy from the site directory
				$res = copy( $dbwork_src, $dbwork_dst );
self::debug(__METHOD__.'():' . __LINE__ . " copy('{$dbwork_src}', '{$dbwork_dst}'):" . var_export($res, TRUE));
			}
			if ( ! file_exists( $dbwork_dst ) ) {
				// as a fallback, copy from the temp directory
				$dbwork_src = $ds_runtime->last_ui_event->info[2] . 'database.sql';
				$res = copy( $dbwork_src, $dbwork_dst );
self::debug(__METHOD__.'():' . __LINE__ . " copy('{$dbwork_src}', '{$dbwork_dst}'):" . var_export($res, TRUE));
			}
			if ( ! file_exists ( $dbwork_dst ) ) {
				self::debug(__METHOD__.'():' . __LINE__ . ' cannot find database.sql file. free space: ' . disk_free_space( $ds_runtime->last_ui_event->info[1] ));
			}

$dir = $ds_runtime->last_ui_event->info[2];
$files = scandir( $dir );
self::debug(__METHOD__.'():' . __LINE__ . ' files in "' . $dir . '": ' . var_export($files, TRUE));
$dir = $ds_runtime->last_ui_event->info[1];
$files = scandir( $dir );
self::debug(__METHOD__.'():' . __LINE__ . ' files in "' . $dir . '": ' . var_export($files, TRUE));
		}

		/**
		 * Process the newly created site, cleaning it up and preparing it for local hosting
		 */
		public function process()
		{
			$this->backup_ext = '-sav-' . time();
self::debug(__METHOD__.'(): starting clean import process');

			global $ds_runtime;
self::debug(__METHOD__.'():' . __LINE__ . ' info: ' . var_export($ds_runtime->last_ui_event, TRUE));

			// ensure trailing directory separator for both platforms #34
			if ( isset( $ds_runtime->last_ui_event->info[2] ) )
				$ds_runtime->last_ui_event->info[2] = rtrim( $ds_runtime->last_ui_event->info[2], '\\/' ) . DIRECTORY_SEPARATOR;

			$this->site_name = $siteName = $ds_runtime->last_ui_event->info[0];
self::debug(__METHOD__.'():' . __LINE__ . ' site name=' . $this->site_name);
			$this->site_path = $this->trailingslashit( $ds_runtime->preferences->sites->{$siteName}->sitePath );
self::debug(__METHOD__.'():' . __LINE__ . ' path=' . $this->site_path);

			// find the directory that WordPress is in
			$wpconfig_path = DS_Utils::find_first_file( $this->site_path, 'wp-config.php' );
$wpconfig_path = $this->site_path . 'wp-config.php';
			$this->install_path = $this->trailingslashit( substr( $wpconfig_path, 0, -13 ) );
self::debug(__METHOD__.'():' . __LINE__ . ' install_path=' . $this->install_path);

			// get location of config file
			$wpconfig = $this->install_path . 'wp-config.php';

			// load all plugin-specific directories into a single array
			$jetpack_dir = $this->install_path . 'wp-content/plugins/jetpack';

self::debug(__METHOD__.'():' . __LINE__ . ' backup wp-config');
			// backup the wp-config file
			if ( file_exists( $wpconfig ) ) {
				copy( $wpconfig, $wpconfig . $this->backup_ext );
			}

			// collect wp-config data
			$this->config_file = $wp_normal_config_file = new DS_ConfigFile( $wpconfig );
self::debug(__METHOD__.'():' . __LINE__ . ' config_file=' . $wpconfig );
self::debug(__METHOD__.'():' . __LINE__ . ' contents=' . file_get_contents($wpconfig));

			$db_user = $wp_normal_config_file->get_key( 'DB_USER' );
			$db_password = $wp_normal_config_file->get_key( 'DB_PASSWORD' );
			$wp_normal_config_file->set_type( 'php-variable' );
			$table_prefix = $wp_normal_config_file->get_key( 'table_prefix' );

self::debug(__METHOD__.'()' . __LINE__ . ' dbuser=' . $db_user . '  db_pass=' . $db_password . '  prefix=' . $table_prefix);

			// if missing, get wp-config settings information from Preferences file

			$site_entry = $ds_runtime->preferences->sites->{$siteName};

			// check database user and password
			if ( empty( $db_user ) && empty( $db_password ) ) {
				$db_user = $site_entry->dbName;
				$db_password = $site_entry->dbPass;
			}

self::debug(__METHOD__.'():' . __LINE__ . ' table prefix=' . var_export($table_prefix, TRUE));
			// find the table prefix
			if ( empty( $table_prefix ) ) {
				$db_file = $this->install_path . self::DB_WORK_FILE;
self::debug(__METHOD__.'():' . __LINE__ . ' searching database file: ' . $db_file);
				if ( file_exists( $db_file ) ) {
					$fh = fopen( $db_file, 'r' );
					if ( FALSE !== $fh ) {
						do {
							$line = fgets( $fh );
							if ( FALSE !== $line ) {
								// look for a line like: "CREATE TABLE `xxx_posts` ("
								if ( FALSE !== ( $create = stripos( $line, 'CREATE TABLE `' ) ) &&
									FALSE !== ( $posts = stripos( $line, '_options` (' ) ) ) {
									$start = $create + 14;
									$table_prefix = substr( $line, $start, $posts - $start + 1 );
								}
							}
						} while ( empty( $table_prefix ) && ! feof( $fh ) );
						fclose( $fh );
						@unlink( $db_file );
					} else {
self::debug(__METHOD__.'():' . __LINE__ . ' ERROR: cannot open ds-dbwork.tmp file' . $db_file);
					}
				} else {
self::debug(__METHOD__.'():' . __LINE__ . ' ERROR: unable to find ds-dbwork.tmp file ' . $db_file);
				}
			}

			// find WP_HOME and WP_SITEURL and comment them out
			$wp_normal_config_file->set_type( 'php-define' );
			$wp_home = $wp_normal_config_file->get_key( 'WP_HOME', FALSE );
			if ( FALSE !== $wp_home )
				$wp_normal_config_file->comment();
			$wp_siteurl = $wp_normal_config_file->get_key( 'WP_SITEURL', FALSE );
			if ( FALSE !== $wp_siteurl )
				$wp_normal_config_file->comment();

self::debug(__METHOD__.'()' . __LINE__ . ' setting DB configurations');
			// set the configuration info in the new wp-config
			$source = dirname( __FILE__ ) . '/lib/wp-config-sample.php';
			$this->new_config = $clean_config_file = new DS_ConfigFile( $source );
			$clean_config_file->set_key( 'DB_USER', $db_user );
			$clean_config_file->set_key( 'DB_NAME', $db_user );
			$clean_config_file->set_key( 'DB_PASSWORD', $db_password );
			$clean_config_file->set_key( 'DB_HOST', '127.0.0.1' );

self::debug(__METHOD__.'()' . __LINE__ . ' setting salts');
			// set the salts
			$clean_config_file->set_key( 'AUTH_KEY', DS_Utils::random_salt() );
			$clean_config_file->set_key( 'SECURE_AUTH_KEY', DS_Utils::random_salt() );
			$clean_config_file->set_key( 'LOGGED_IN_KEY', DS_Utils::random_salt() );
			$clean_config_file->set_key( 'NONCE_KEY', DS_Utils::random_salt() );
			$clean_config_file->set_key( 'AUTH_SALT', DS_Utils::random_salt() );
			$clean_config_file->set_key( 'SECURE_AUTH_SALT', DS_Utils::random_salt() );
			$clean_config_file->set_key( 'LOGGED_IN_SALT', DS_Utils::random_salt() );
			$clean_config_file->set_key( 'NONCE_SALT', DS_Utils::random_salt() );

			// MultiSite info
			$is_multisite = $wp_normal_config_file->get_key( 'MULTISITE' );
			// TODO: add check for $is_multisite == 'true'
			if ( ! empty( $is_multisite ) ) {
				$multisite = $this->load_class( 'MultiSite' );
				$multisite->clean_import();
			}

			$clean_config_file->isolate_block( 'The name of the database for WordPress', '/**#@-*/' );

			// JetPack
			if ( file_exists ( $jetpack_dir ) ) {
self::debug(__METHOD__.'()' . __LINE__ . ' JetPack detected - setting DEV mode');
				$this->new_config->isolate_block( '/**#@-*/', '/* That\'s all, stop editing! Happy blogging. */' );
				$clean_config_file->set_key( 'JETPACK_DEV_DEBUG', 'TRUE' );
			}

			// remove block isolation
			$clean_config_file->isolate_block( NULL, NULL );

self::debug(__METHOD__.'()' . __LINE__ . ' setting table prefix');
			$clean_config_file->set_type( 'php-variable' );
			$clean_config_file->set_key( 'table_prefix', $table_prefix );

			// TODO: set WP_HOME and WP_SITEURL constants

self::debug(__METHOD__.'()' . __LINE__ . ' saving new config file');
			// save the new wp-config file
			// TODO: move this after plugin-specific updates??
			$clean_config_file->save( $wpconfig );

			//
			// the following are hosting-specific adjustments
			//

			// The following hosting providers are exclusive. Once we find one, we
			// don't need to invoke any others.
			$found_hosting = FALSE;

			// Flywheel specific actions
			// TODO: move constants list into classes and add ->has_constants() method
			// TOOD: to detect usage
			$flywheel_constants = array(
				'FLYWHEEL_PLUGIN_DIR',
			);
			if ( $this->has_constant( $flywheel_constants ) ) {
self::debug(__METHOD__.'()' . __LINE__ . ' found presence of Flywheel host platform - removing references');
				require __DIR__ . '/inc/flywheel.php';		
				$flywheel = new DS_Clean_Import_Flywheel();
				$flywheel->clean_import();
				$found_hosting = TRUE;
			}

			if ( ! $found_hosting ) {
				// WPEngine specific actions
				$wpengine_constants = array(
					'WPE_APIKEY',
					'WPE_SFTP_PORT',
					'WPE_CLUSTER_ID',
				);
				if ( $this->has_constant( $wpengine_constants ) ) {
self::debug(__METHOD__.'()' . __LINE__ . ' found presence of WPEngine host platform - removing references');
					$wpengine = $this->load_class( 'WPEngine' );
					$wpengine->clean_import();
					$found_hosting = TRUE;
				}
			}

			if ( ! $found_hosting ) {
				// Endurance specific actions
				$endurance = $this->load_class( 'Endurance' );
				$endurance->clean_import();
				$found_hosting = TRUE;
			}
self::debug(__METHOD__.'():' . __LINE__ . ' found hosting=' . var_export($found_hosting, TRUE));

			// completed host-specific changes

			// do final processing
self::debug(__METHOD__.'()' . __LINE__ . ' final processing');
			$this->htaccess();						// process the htaccess file

			$this->rename_plugin_files();			// rename / disable plugins

			$this->remove_cache_dirs();				// remove cache directories

			$this->comment_cache_settings();		// remove / disable cache settings

self::debug(__METHOD__.'()' . __LINE__ . ' running database cleanup...');
			$database = $this->load_class( 'Database' );
			$database->clean_import( $db_user, $db_password, $table_prefix );

			// TODO: remove known plugins from the 'installed_plugin' options entry
			// TODO: resave permalink settings / flush rewrite rules
self::debug(__METHOD__.'()' . __LINE__ . ' clean import processing complete');
			$this->close_log();
		}

		/**
		 * Loads a class file and creates and instance of the class
		 * @param string $class The class name to be loaded. Assumes class extends the DS_Clean_Import_Base class.
		 * @return Object And instance of the class
		 */
		private function load_class( $class )
		{
self::debug(__METHOD__."('{$class}')");
			require __DIR__ . '/inc/' . strtolower( $class ) . '.php';
			$classname = 'DS_Clean_Import_' . $class;
			$instance = new $classname();
			return $instance;
		}

		/**
		 * Recursively delete a directory
		 *
		 * @param string $dir Directory name
		 * @param boolean $deleteRootToo Delete specified top-level directory as well
		 */
		private function unlink_recursive( $dir, $deleteRootToo )
		{
			if ( ! $dh = @opendir( $dir ) )
				return;

			while ( FALSE !== ( $obj = readdir( $dh ) ) ) {
				if ( '.' === $obj || '..' === $obj )
					continue;

				if ( ! @unlink( $dir . '/' . $obj ) ) {
					$this->unlink_recursive( $dir . '/' . $obj, TRUE );
				}
			}

			closedir( $dh );

			if ( $deleteRootToo )
				@rmdir( $dir );

			return;
		}

		/**
		 * Process htaccess file. Rename and replace it's contents with "standard" WP rules
		 */
		private function htaccess()
		{
DS_Clean_Import::debug(__METHOD__.'()' . __LINE__ . ' rebuiling .htaccess file');
			$htaccess = $this->install_path . '.htaccess';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' htaccess=' . $htaccess);
			// see if .htaccess exists, if does rename
			if ( file_exists( $htaccess ) ) {
DS_Clean_Import::debug(__METHOD__.'()' . __LINE__ . ' saving original .htaccess file');
				rename( $htaccess, $htaccess . $this->backup_ext );
			}

			// create default WordPress .htaccess file
			$contents = "# Reset by DesktopServer\n";
			$contents .= "# BEGIN WordPress\n";
			$contents .= "<IfModule mod_rewrite.c>\n";
			$contents .= "RewriteEngine On\n";
			$contents .= "RewriteBase /\n";
			$contents .= "RewriteRule ^index\.php$ - [L]\n";
			$contents .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
			$contents .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
			$contents .= "RewriteRule . /index.php [L]\n";
			$contents .= "</IfModule>\n";
			$contents .= "# END WordPress\n"; 

DS_Clean_Import::debug(__METHOD__.'()' . __LINE__ . ' recreating .htaccess contents');
			file_put_contents( $htaccess, $contents );
		}

		/**
		 * Renames a list of known plugins and their directories, effectively disabling them.
		 */
		private function rename_plugin_files()
		{
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renaming plugins that can interfere with local hosting');
			$plugins = $this->load_class( 'Plugins' );
			$plugins->clean_import();
		}

		/**
		 * Removes any directories used by caching plugins
		 */
		private function remove_cache_dirs()
		{
DS_Clean_Import::debug(__METHOD__.'()' . __LINE__ . ' check for any cache directories');
			$directories = array(
				$this->install_path . 'wp-content/cache',
			);

			// see if any cache folders exist. if so delete them
			foreach ( $directories as $dir ) {
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' dir=' . $dir);
				if ( is_dir( $dir ) ) {
DS_Clean_Import::debug(__METHOD__.'()' . __LINE__ . ' found directory "' . $dir . '" - removing');
					$this->unlink_recursive( $dir, TRUE );
				}
			}
		}

		/**
		 * Comments out cache settings in the wp-config.php file
		 */
		private function comment_cache_settings()
		{
DS_Clean_Import::debug(__METHOD__.'()' . __LINE__ . ' remove any caching settings from wp-config');
			$wpconfig = $this->install_path . 'wp-config.php';

 			/**
 			 * Read in wp-config.php and comment out cache settings
 			 *
			 * Regular expression matches:
			 * define('WP_CACHE', true); //Added by WP-Cache Manager
			 * define( 'WPCACHEHOME', '//Volumes/Data/Users/GreggFranklin/Documents/Websites/super-cache.dev/wp-content/plugins/wp-super-cache/' ); //Added by WP-Cache Manager
			 * define('WP_CACHE', true); // Added by W3 Total Cache
			 */
			$filedata = file( $wpconfig );
			$newdata = preg_replace( '/(define\(\s?[\'"]WP_?CACHE)/', '// $1', $filedata );

//			foreach ( $filedata as $filerow ) {
//				if (FALSE !== in_array( $filerow, $lookfor ) )
//					$filerow = $newtext;
//				$newdata[] = $filerow;
//			}
			file_put_contents( $wpconfig, $newdata );
DS_Clean_Import::debug(__METHOD__.'()' . __LINE__ . ' wrote ' . count( $newdata ) . ' lines to wp-config');
		}

		/**
		 * Check if one of a list of constants exists in the config file.
		 * @param array $constants List of constants to search for
		 * @return boolean TRUE if one or more of the constants exists; otherwise FALSE
		 */
		private function has_constant( $constants )
		{
			foreach ( $constants as $constant ) {
				$config_value = $this->config_file->get_key( $constant );
				if ( ! empty( $config_value ) ) {
					return TRUE;
				}
			}
			return FALSE;
		}

		/**
		 * Adds a single trailing forward slash to the end of the string
		 * @param string $val The value to append the slash to
		 * @return string The modified string, with a trailing slash
		 */
		private function trailingslashit( $val )
		{
			$val = rtrim( $val, '/\\' ) . '/';
			return $val;
		}

		/**
		 * Performs logging for debugging purposes
		 * @param string $msg The message to write to the log file
		 */
		public static function debug( $msg )
		{
			if ( self::DEBUG_LOG ) {
				if ( NULL === self::$_log_file ) {
					// TODO: put this in the DS tmp directory
					self::$_log_file = dirname( __DIR__ ) . '/~clean-import-log.txt';
					self::$_log = @fopen( self::$_log_file, 'a+' );
				}

				if ( FALSE !== self::$_log ) {
					if ( NULL === $msg )
						fwrite( self::$_log, date( 'Y-m-d H:i:s' ) );
					else
						fwrite( self::$_log, date( 'Y-m-d H:i:s - ' ) . $msg . "\r\n" );
					fflush( self::$_log );
				}
			}
			// send logging to the Debug and Trace plugin if active
			if ( function_exists( 'trace' ) )
				trace( $msg );
		}

		/**
		 * Close the log file
		 */
		private function close_log()
		{
			// TODO: may not be needed
			if ( FALSE !== self::$_log ) {
				fclose( self::$_log );
				self::$_log = FALSE;
			}
		}
	}
}


error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);

// setup filter for WP Admin pages
global $wp_filter;
$wp_filter['wp_loaded'][0]['ds_clean_import_admin'] = array( 'function' => 'ds_clean_import_admin', 'accepted_args' => 0 );

function ds_clean_import_admin()
{
	DS_Clean_Import::debug('ds_clean_import_admin() callback');
	require_once __DIR__ . '/inc/admin.php';
}

DS_Clean_Import::debug('***starting');
global $ds_runtime;

if ( FALSE !== $ds_runtime->last_ui_event )
	DS_Clean_Import::debug('>>last UI event: "' . $ds_runtime->last_ui_event->action . '"');

// do some checks and exit early if we don't need to process anything
if ( ! isset($ds_runtime) ) {
DS_Clean_Import::debug('-no $ds_runtime instance - skipping');
	return;
}
if ( ! $ds_runtime->is_localhost ) {
DS_Clean_Import::debug('-is_localhost is FALSE');
	return;
}
if ( FALSE === $ds_runtime->last_ui_event ) {
DS_Clean_Import::debug('-last_ui_event is FALSE');
	return;
}
if ( ! in_array( $ds_runtime->last_ui_event->action, array( 'site_imported', 'site_preimport' ) ) ) {
DS_Clean_Import::debug('-action not triggering initialization: "' . $ds_runtime->last_ui_event->action . '"' );
	return;
}

DS_Clean_Import::debug('***have an actionable event - process the site');
/**
 * Load library classes
 */
include_once $ds_runtime->htdocs_dir . '/classes/gstring.php';
include_once $ds_runtime->htdocs_dir . '/classes/class-ds-utils.php';
include_once $ds_runtime->htdocs_dir . '/classes/class-ds-config-file.php';

$clean_import = new DS_Clean_Import();

switch ( $ds_runtime->last_ui_event->action ) {
case 'site_preimport':
	$clean_import->pre_import_process();
	break;

case 'site_imported':
	$clean_import->process();
	break;
}

// EOF