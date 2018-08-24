<?php

/** 
 * Delcare Clean Import Classes
 */
if ( ! class_exists( 'DS_CLEAN_IMPORT', FALSE ) ) {
	class DS_Clean_Import
	{
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
			require_once( dirname( __FILE__ ) . '/inc/base.php' );
		}

		/**
		 * Processor for 'site_preimport' events
		 */
		public function pre_import_process()
		{
			global $ds_runtime;
self::debug(__METHOD__.'(): ui event=' . var_export($ds_runtime->last_ui_event, TRUE));

			$duplicator = $this->load_class( 'Duplicator' );
			$duplicator->pre_import_process( $ds_runtime->last_ui_event->info );

			$aria = $this->load_class( 'Aria_Engine' );
			$aria->pre_import_process( $ds_runtime->last_ui_event->info );
		}

		/**
		 * Process the newly created site, cleaning it up and preparing it for local hosting
		 */
		public function process()
		{
			$this->backup_ext = '-sav-' . time();
self::debug(__METHOD__.'() starting clean import process');

			global $ds_runtime;
			$this->site_name = $siteName = $ds_runtime->last_ui_event->info[0];
			$this->site_path = $this->trailingslashit( $ds_runtime->preferences->sites->{$siteName}->sitePath );

			// find the directory that WordPress is in
			$wpconfig_path = DS_Utils::find_first_file( $this->site_path, 'wp-config.php' );
			$this->install_path = $this->trailingslashit( substr( $wpconfig_path, 0, -13 ) );
self::debug(__METHOD__.'() site_path=' . $this->site_path);
self::debug(__METHOD__.'() install_path=' . $this->install_path);

			// get location of config file
			$wpconfig = $this->install_path . 'wp-config.php';

			// load all plugin-specific directories into a single array
			$jetpack_dir = $this->install_path . 'wp-content/plugins/jetpack';

self::debug(__METHOD__.'() backup wp-config');
			// backup the wp-config file
			if ( file_exists( $wpconfig ) ) {
				copy( $wpconfig, $wpconfig . $this->backup_ext );
			}

			// collect wp-config data
			$this->config_file = $wp_normal_config_file = new DS_ConfigFile( $wpconfig );

			$db_user = $wp_normal_config_file->get_key( 'DB_USER' );
			$db_password = $wp_normal_config_file->get_key( 'DB_PASSWORD' );
			$wp_normal_config_file->set_type( 'php-variable' );
			$table_prefix = $wp_normal_config_file->get_key( 'table_prefix' );
			$wp_normal_config_file->set_type( 'php-define' );

			$wp_home = $wp_normal_config_file->get_key( 'WP_HOME' );
			$wp_siteurl = $wp_normal_config_file->get_key( 'WP_SITEURL' );
self::debug(__METHOD__.'() dbuser=' . $db_user . '  db_pass=' . $db_password . '  prefix=' . $table_prefix . '  home=' . $wp_home);


self::debug(__METHOD__.'() setting DB configurations');
			// set the configuration info in the new wp-config
			$source = dirname( __FILE__ ) . '/lib/wp-config-sample.php';
			$this->new_config = $clean_config_file = new DS_ConfigFile( $source );
			$clean_config_file->set_key( 'DB_USER', $db_user );
			$clean_config_file->set_key( 'DB_NAME', $db_user );
			$clean_config_file->set_key( 'DB_PASSWORD', $db_password );
			$clean_config_file->set_key( 'DB_HOST', '127.0.0.1' );

self::debug(__METHOD__.'() setting salts');
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
			if ( !empty( $is_multisite ) ) {
				$multisite = $this->load_class( 'MultiSite' );
				$multisite->clean_import();
			}

			$clean_config_file->isolate_block('The name of the database for WordPress', '/**#@-*/' );

			// JetPack
			if ( file_exists ( $jetpack_dir ) ) {
self::debug(__METHOD__.'() JetPack detected - setting DEV mode');
				$this->new_config->isolate_block('/**#@-*/', '/* That\'s all, stop editing! Happy blogging. */' );
				$clean_config_file->set_key( 'JETPACK_DEV_DEBUG', 'TRUE' );
			}

			// remove block isolation
			$clean_config_file->isolate_block( NULL, NULL );

self::debug(__METHOD__.'() setting table prefix');
			$clean_config_file->set_type( 'php-variable' );
			$clean_config_file->set_key( 'table_prefix', $table_prefix );

self::debug(__METHOD__.'() saving new config file');
			// save the new wp-config file
			// TODO: move this after plugin-specific updates??
			$clean_config_file->save( $wpconfig );

			//
			// the following are hosting-specific adjustments
			//

			// Flywheel specific actions
			$flywheel_constants = array(
				'FLYWHEEL_PLUGIN_DIR',
			);
			if ( $this->has_constant( $flywheel_constants ) ) {
DS_Clean_Import::debug(__METHOD__.'() found presence of Flywheel host platform - removing references');
				require dirname( __FILE__ ) . '/inc/flywheel.php';		
				$flywheel = new DS_Clean_Import_Flywheel();
				$flywheel->clean_import();
			}

			// WPEngine specific actions
			$wpengine_constants = array(
				'WPE_APIKEY',
				'WPE_SFTP_PORT',
				'WPE_CLUSTER_ID',
			);
			if ( $this->has_constant( $wpengine_constants ) ) {
DS_Clean_Import::debug(__METHOD__.'() found presence of WPEngine host platform - removing references');
				$wpengine = $this->load_class( 'WPEngine' );
				$wpengine->clean_import();
			}

			// Endurance specific actions
			$endurance = $this->load_class( 'Endurance' );
			$endurance->clean_import();

			// completed host-specific changes

			// do final processing
self::debug(__METHOD__.'() final processing');
			$this->htaccess();						// process the htaccess file

			$this->rename_plugin_files();			// rename / disable plugins

			$this->remove_cache_dirs();				// remove cache directories

			$this->comment_cache_settings();		// remove / disable cache settings

self::debug(__METHOD__.'() running database cleanup...');
			$database = $this->load_class( 'Database' );
			$database->clean_import( $db_user, $db_password, $table_prefix );

			// TODO: remove known plugins from the 'installed_plugin' options entry
			// TODO: resave permalink settings / flush rewrite rules
self::debug(__METHOD__.'() clean import processing complete');
			$this->close_log();
		}

		/**
		 * Loads a class file and creates and instance of the class
		 * @param string $class The class name to be loaded. Assumes class extends the DS_Clean_Import_Base class.
		 * @return Object And instance of the class
		 */
		private function load_class( $class )
		{
DS_Clean_Import::debug(__METHOD__."('{$class}')");
			require dirname( __FILE__ ) . '/inc/' . strtolower( $class ) . '.php';
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

			while ( FALSE !== ( $obj = readdir( $dh ) ) )
			{
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
DS_Clean_Import::debug(__METHOD__.'() rebuiling .htaccess file');
			$htaccess = $this->install_path . '.htaccess';

			// see if .htaccess exists, if does rename
			if ( file_exists( $htaccess ) ) {
DS_Clean_Import::debug(__METHOD__.'() saving original .htaccess file');
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

DS_Clean_Import::debug(__METHOD__.'() recreating .htaccess contents');
			file_put_contents( $htaccess, $contents );
		}

		/**
		 * Renames a list of known plugins and their directories, effectively disabling them.
		 */
		private function rename_plugin_files()
		{
DS_Clean_Import::debug(__METHOD__.'() renaming plugins that can interfere with local hosting');
			$plugins = $this->load_class( 'Plugins' );
			$plugins->clean_import();
		}

		/**
		 * Removes any directories used by caching plugins
		 */
		private function remove_cache_dirs()
		{
DS_Clean_Import::debug(__METHOD__.'() check for any cache directories');
			$directories = array(
				$this->install_path . 'wp-content/cache',
			);

			// see if any cache folders exist. if so delete them
			foreach ( $directories as $dir ) {
				if ( is_dir( $dir ) ) {
DS_Clean_Import::debug(__METHOD__.'() found directory "' . $dir . '" - removing');
					$this->unlink_recursive( $dir, TRUE );
				}
			}
		}

		/**
		 * Comments out cache settings in the wp-config.php file
		 */
		private function comment_cache_settings()
		{
DS_Clean_Import::debug(__METHOD__.'() remove any caching settings from wp-config');
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
DS_Clean_Import::debug(__METHOD__.'() wrote ' . count( $newdata ) . ' lines to wp-config');
		}

		/**
		 * Check is one of a list of constants exists in the config file.
		 * @param array $constants List of constants to search for
		 * @return boolean TRUE if one or more of the constants exists; otherwise FALSE
		 */
		private function has_constant( $constants )
		{
			foreach ($constants as $constant ) {
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
			$val = rtrim( $val, '/\\') . '/';
			return $val;
		}

		/**
		 * Performs logging for debugging purposes
		 * @param string $msg The message to write to the log file
		 */
		public static function debug( $msg )
		{
return;
			if ( defined( 'WP_DEBUG' ) ) {
				if ( NULL === self::$_log_file ) {
					self::$_log_file = dirname( dirname( __FILE__ ) ) . '/~clean-import-log.txt';
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
			if ( function_exists( 'trace' ) )
				trace( $msg );
		}

		/**
		 * Close the log file
		 */
		private function close_log()
		{
			// TODO: may not be needed
			if ( FALSE !== self::$_log )
				fclose( self::$_log );
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
	require_once( dirname( __FILE__ ) . '/inc/admin.php' );
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
include_once( $ds_runtime->htdocs_dir . '/classes/gstring.php' );
include_once( $ds_runtime->htdocs_dir . '/classes/class-ds-utils.php' );
include_once( $ds_runtime->htdocs_dir . '/classes/class-ds-config-file.php' );

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