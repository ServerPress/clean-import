<?php
global $ds_runtime;

if ( !$ds_runtime->is_localhost ) return;
if ( $ds_runtime->last_ui_event === false ) return;
if ( $ds_runtime->last_ui_event->action !== "site_imported" ) return;

/**
 * Get classes
 */
include_once( $ds_runtime->htdocs_dir . '/classes/string.php' );
include_once( $ds_runtime->htdocs_dir . '/classes/class-ds-utils.php' );
include_once( $ds_runtime->htdocs_dir . '/classes/class-ds-config-file.php' );

/** 
 * Implement Clean Import
 */
if ( ! class_exists( 'DS_CLEAN_IMPORT' ) ) {

	class DS_Clean_Import {
    
		static function reset() {

			global $ds_runtime;    
            $siteName = $ds_runtime->last_ui_event->info[0];
            $sitePath = $ds_runtime->preferences->sites->{$siteName}->sitePath;
            
            // Find the directory that WordPress is in
            $wpconfig_path = DS_Utils::find_first_file( $sitePath, "wp-config.php" );
			$path = substr($wpconfig_path, 0, -13);

			// Collect needed configuration info
            $advanced_cache = $path . '/wp-content/advanced-cache.php';
            $object_cache = $path . '/wp-content/object-cache.php';
            $cache_dir = $path . '/wp-content/cache';
            $wpconfig = $path . '/wp-config.php';
            $htaccess = $sitePath . '/.htaccess';
                
            // WP3 Total Cache
            $w3tc_config_dir = $path . '/wp-content/w3tc-config';
            $w3_total_cache_dir = $path . '/wp-content/plugins/w3-total-cache'; 
                
            // WP Rocket              
            $wp_rocket_config_dir = $path . '/wp-content/wp-rocket-config';
			$wp_rocket_cache_dir = $path . '/wp-content/plugins/wp-rocket';
                
            // WP Super Cache
            $wp_super_cache_config = $path . '/wp-content/wp-cache-config.php';
            $wp_super_cache_dir = $path . '/wp-content/plugins/wp-super-cache';
                
            // iThemes Security Pro
            $ithemes_security_pro = $path . '/wp-content/plugins/ithemes-security-pro';
                
            // Backup the wp-config file
            if ( file_exists( $wpconfig ) ) {
                copy( $wpconfig, $wpconfig . '-sav-' . time()  );
            }
            	
            // Collect wp-config data
            $wp_normal_config_file = new DS_ConfigFile( $wpconfig );
            $db_user = $wp_normal_config_file->get_key( 'DB_USER' );
            $db_password = $wp_normal_config_file->get_key( 'DB_PASSWORD' );
            $wp_normal_config_file->set_type( 'php-variable' );
            $table_prefix = $wp_normal_config_file->get_key( 'table_prefix' );	
            $wp_normal_config_file->set_type( 'php-define' );
            	
            $wp_home = $wp_normal_config_file->get_key( 'WP_HOME' );
            $wp_siteurl = $wp_normal_config_file->get_key( 'WP_SITEURL' );
            	
            // Collect Multisite configuration data
            $wp_allow_multisite = $wp_normal_config_file->get_key( 'WP_ALLOW_MULTISITE' );
            $multisite = $wp_normal_config_file->get_key( 'MULTISITE' );
            $subdomain_install = $wp_normal_config_file->get_key( 'SUBDOMAIN_INSTALL' );
            $domain_current_site = $wp_normal_config_file->get_key( 'DOMAIN_CURRENT_SITE' );
            $path_current_site = $wp_normal_config_file->get_key( 'PATH_CURRENT_SITE' );
            $site_id_current_site = $wp_normal_config_file->get_key( 'SITE_ID_CURRENT_SITE' );
            $blog_id_current_site = $wp_normal_config_file->get_key( 'BLOG_ID_CURRENT_SITE' );

            // Set the configuration info in the new wp-config
            $source = dirname( __FILE__ ) . '/lib/wp-config-sample.php';
            $clean_config_file = new DS_ConfigFile( $source );
            $clean_config_file->set_key( 'DB_USER', $db_user );
            $clean_config_file->set_key( 'DB_NAME', $db_user );
            $clean_config_file->set_key( 'DB_PASSWORD', $db_password );
	        $clean_config_file->set_key( 'DB_HOST', '127.0.0.1' );
	        	
	        if ( !empty( $wp_home ) ) {
	        	$clean_config_file->set_key( 'WP_HOME', '' );
	        }
	        	
	        if ( !empty( $wp_siteurl ) ) {
	        	$clean_config_file->set_key( 'WP_SITEURL', '' );
	        }
	        	
            // Set the salts
            $clean_config_file->set_key( 'AUTH_KEY', DS_Utils::random_salt() );
            $clean_config_file->set_key( 'SECURE_AUTH_KEY', DS_Utils::random_salt() );
            $clean_config_file->set_key( 'LOGGED_IN_KEY', DS_Utils::random_salt() );
            $clean_config_file->set_key( 'NONCE_KEY', DS_Utils::random_salt() );
            $clean_config_file->set_key( 'AUTH_SALT', DS_Utils::random_salt() );
            $clean_config_file->set_key( 'SECURE_AUTH_SALT', DS_Utils::random_salt() );
            $clean_config_file->set_key( 'LOGGED_IN_SALT', DS_Utils::random_salt() );
            $clean_config_file->set_key( 'NONCE_SALT', DS_Utils::random_salt() );
            	
            // Multisite info
            if ( !empty( $multisite ) ) {
               	$clean_config_file->set_key( 'WP_ALLOW_MULTISITE', $wp_allow_multisite );
                $clean_config_file->set_key( 'MULTISITE', $multisite );
                $clean_config_file->set_key( 'SUBDOMAIN_INSTALL', $subdomain_install );
                $clean_config_file->set_key( 'DOMAIN_CURRENT_SITE', $domain_current_site );
                $clean_config_file->set_key( 'PATH_CURRENT_SITE', $path_current_site );
                $clean_config_file->set_key( 'SITE_ID_CURRENT_SITE', $site_id_current_site );
                $clean_config_file->set_key( 'BLOG_ID_CURRENT_SITE', $blog_id_current_site );
            }

            $clean_config_file->set_type( 'php-variable' );
            $clean_config_file->set_key( 'table_prefix', $table_prefix );
       			
            /**
             * WPEngine specific actions
             */
            $is_wpengine_config_file = false;
            $wpengine_constants = array(
                'WPE_APIKEY',
                'WPE_SFTP_PORT',
                'WPE_CLUSTER_ID',
            );
            foreach ($wpengine_constants as $constant ) {
                $config_value = $wp_normal_config_file->get_key( $constant );
                if ( !empty( $config_value ) ) {
                    $is_wpengine_config_file = true;
                    	require dirname( __FILE__ ) . '/inc/wpengine.php';
                }
            }
            	
            // See if .htaccess exists, if does rename
            if ( file_exists( $htaccess ) ) {
                rename( $htaccess, $htaccess . '-sav-'.time() );  
            }

            // Create default WordPress .htaccess file
            $txt = '';
            $txt  = "# Reset by DesktopServer \n";
            $txt .= "# BEGIN WordPress \n";
            $txt .= "<IfModule mod_rewrite.c> \n";
            $txt .= "RewriteEngine On \n";
            $txt .= "RewriteBase / \n";
            $txt .= "RewriteRule ^index\.php$ - [L] \n";
            $txt .= "RewriteCond %{REQUEST_FILENAME} !-f \n";
            $txt .= "RewriteCond %{REQUEST_FILENAME} !-d \n";
            $txt .= "RewriteRule . /index.php [L] \n";
            $txt .= "</IfModule> \n";
            $txt .= "# END WordPress \n"; 

            file_put_contents( $htaccess, $txt );
                  
            //Rename cache, security files and folders
            $files_to_rename = array(
                $advanced_cache,
                $object_cache,
                $wp_super_cache_config,
                $wp_super_cache_dir,
                $w3tc_config_dir,
                $w3_total_cache_dir,
                $wp_rocket_config_dir,
                $wp_rocket_cache_dir,
                $ithemes_security_pro,
            );
            
            foreach	( $files_to_rename as $file_to_rename ) {
				if ( file_exists( $file_to_rename ) ) {
					rename( $file_to_rename, $file_to_rename . '-sav-' . time() );  
				}
            }      
                
            // See if cache folder exists, if does delete
            if ( is_dir( $cache_dir ) ) {
                          
				/**
 				 * Recursively delete a directory
 				 *
 				 * @param string $dir Directory name
 				 * @param boolean $deleteRootToo Delete specified top-level directory as well
 				 */
				function unlinkRecursive($dir, $deleteRootToo)
				{
    				if(!$dh = @opendir($dir))
    				{
        				return;
    				}
    				while (false !== ($obj = readdir($dh)))
    				{
        				if($obj == '.' || $obj == '..')
        				{
            				continue;
        				}

        				if (!@unlink($dir . '/' . $obj))
        				{
            				unlinkRecursive($dir.'/'.$obj, true);
        				}
    				}

    				closedir($dh);

    				if ($deleteRootToo)
    				{
        				@rmdir($dir);
    				}

    			return;
			}
			unlinkRecursive($cache_dir, 1);
    
            }
                
 			/**
 			 * Read in wp-config.php and comment out cache calls
 			 *
			 * Regular expression matches:
			 * define('WP_CACHE', true); //Added by WP-Cache Manager
			 * define( 'WPCACHEHOME', '//Volumes/Data/Users/GreggFranklin/Documents/Websites/super-cache.dev/wp-content/plugins/wp-super-cache/' ); //Added by WP-Cache Manager
			 * define('WP_CACHE', true); // Added by W3 Total Cache
			 */
			$filedata = file($wpconfig);
			$newdata = preg_replace('/(define\(\s?[\'"]WP_?CACHE)/', '// $1', $filedata);

			foreach ($filedata as $filerow) {
  				if (in_array($filerow, $lookfor) !== false)
    				$filerow = $newtext;
  					$newdata[] = $filerow;
				}
				file_put_contents($wpconfig, $newdata);
        }
    }

    DS_Clean_Import::reset();
}