<?php
global $ds_runtime;

if ( !$ds_runtime->is_localhost ) return;
if ( $ds_runtime->last_ui_event === false ) return;
if ( $ds_runtime->last_ui_event->action !== "site_imported" ) return;

// Get classes
include_once( $ds_runtime->htdocs_dir . '/classes/string.php' );
include_once( $ds_runtime->htdocs_dir . '/classes/class-ds-utils.php' );
include_once( $ds_runtime->htdocs_dir . '/classes/class-ds-config-file.php' );

// Implement Developer reset
if ( ! class_exists( 'DS_CLEAN_IMPORT' ) ) {

    class DS_Clean_Import {
    
        static function reset() {

            global $ds_runtime;    
            if ( $ds_runtime->last_ui_event === false ) return;
            if ( $ds_runtime->last_ui_event->action === 'site_imported' ) {

				trace(' Begin! ');
				// Collect needed configuration info
                $siteName = $ds_runtime->last_ui_event->info[0];
                $mu_plugins = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/mu-plugins';
                $advanced_cache = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/advanced-cache.php';
                $object_cache = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/object-cache.php';
                $cache_dir = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/cache';
                $wpconfig = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-config.php';
                $wpsettings = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-settings.php';
                $htaccess = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/.htaccess';
                
                // WP3 Total Cache
                $w3tc_config_dir = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/w3tc-config';
                $w3_total_cache_dir = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/plugins/w3-total-cache'; 
                
                // WP Rocket              
                $wp_rocket_config_dir = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/wp-rocket-config';
				$wp_rocket_cache_dir = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/plugins/wp-rocket';
                
                // WP Super Cache
                $wp_super_cache_config = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/wp-cache-config.php';
                $wp_super_cache_dir = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/plugins/wp-super-cache';
                
                // iThemes Security Pro
                $ithemes_security_pro = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/plugins/ithemes-security-pro';
                
                // Backup the wp-config file
            	if ( file_exists( $wpconfig ) ) {
                	copy( $wpconfig, $wpconfig . '-sav' );
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
            		
                /*
                 * Flywheel specific actions
                 *
                 */
            	$is_flywheel_config_file = false;
            	$flywheel_constants = array(
                	'FLYWHEEL_PLUGIN_DIR',
            	);
            	trace($flywheel_constants);
            	foreach ($flywheel_constants as $constant ) {
                	$config_value = $wp_normal_config_file->get_key( $constant );
                	trace($config_value);
                	if ( !empty( $config_value ) ) {
                    	$is_flywheel_config_file = true;
                    	trace($is_flywheel_config_file);
                    	//require __DIR__ . '/inc/flywheel.php';
                	}
            	}
       	
            	
                /*
                 * WPEngine specific actions
                 *
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
                    	// require __DIR__ . '/inc/wpengine.php';
                	}
            	}

				//Saves config
            	$clean_config_file->save( $wpconfig );

            	// See if mu-plugins folder exists, if does rename
            	if ( is_dir( $mu_plugins ) ) {

                	rename( $mu_plugins, $mu_plugins . '-sav' ); 
            	}
            	
                // See if .htaccess exists, if does rename
                if ( file_exists( $htaccess ) ) {
                   
                   rename( $htaccess, $htaccess . '-sav' );  
                }

                // Create default WordPress .htaccess file
                $txt = '';
                if ( PHP_OS == 'Darwin' ){
                    // Do Mac stuff
                    $txt  = "# Reset by DesktopServer\n";
                    $txt .= "# BEGIN WordPress \n";
                    $txt .= "<IfModule mod_rewrite.c> \n";
                    $txt .= "Rewrite_normal On \n";
                    $txt .= "RewriteBase / \n";
                    $txt .= "RewriteRule ^index\.php$ - [L] \n";
                    $txt .= "RewriteCond %{REQUEST_FILENAME} !-f \n";
                    $txt .= "RewriteCond %{REQUEST_FILENAME} !-d \n";
                    $txt .= "RewriteRule . /index.php [L] \n";
                    $txt .= "</IfModule> \n";
                    $txt .= "# END WordPress \n"; 
                }else{
                    // Do Windows stuff
                    $txt  = "#Reset by DesktopServer\n";
                    $txt .= "# BEGIN WordPress \n";
                    $txt .= "<IfModule mod_rewrite.c> \n";
                    $txt .= "Rewrite_normal On \n";
                    $txt .= "RewriteBase / \n";
                    $txt .= "RewriteRule ^index\.php$ - [L] \n";
                    $txt .= "RewriteCond %{REQUEST_FILENAME} !-f \n";
                    $txt .= "RewriteCond %{REQUEST_FILENAME} !-d \n";
                    $txt .= "RewriteRule . /index.php [L] \n";
                    $txt .= "</IfModule> \n";
                    $txt .= "# END WordPress \n";
                }
                file_put_contents( $htaccess, $txt );
                  
                // See if advanced-cache.php exists, if does rename
                if ( file_exists( $advanced_cache ) ) {
                   
                   rename( $advanced_cache, $advanced_cache . '-sav' );  
                }
                
                // See if object_cache.php exists, if does rename
                if ( file_exists( $object_cache ) ) {
                   
                   rename( $object_cache, $object_cache . '-sav' );   
                }
                
                // See if wp-cache-config.php exists, if does rename
                if ( file_exists( $wp_super_cache_config ) ) {
                   
                   rename( $wp_super_cache_config, $wp_super_cache_config . '-sav' );    
                }
                
                // See if plugins/wp-super-cache folder exists, if does rename
                if ( is_dir( $wp_super_cache_dir ) ) {
                
                    rename( $wp_super_cache_dir, $wp_super_cache_dir . '-sav' ); 
                }
                
                // See if w3tc-config folder exists, if does rename
                if ( is_dir( $w3tc_config_dir ) ) {
                
                    rename( $w3tc_config_dir, $w3tc_config_dir . '-sav' );  
                }
                
                // See if plugins/w3-total-cache folder exists, if does rename
                if ( is_dir( $w3_total_cache_dir ) ) {
                
                    rename( $w3_total_cache_dir, $w3_total_cache_dir . '-sav' );
                }
                
                // See if wp-rocket-config folder exists, if does rename
                if ( is_dir( $wp_rocket_config_dir ) ) {
                
                    rename( $wp_rocket_config_dir, $wp_rocket_config_dir . '-sav' );
                }
                
                // See if plugins/wp-rocket folder exists, if does rename
                if ( is_dir( $wp_rocket_cache_dir ) ) {
                
                    rename( $wp_rocket_cache_dir, $wp_rocket_cache_dir . '-sav' );
                }
                
                // See if ithemes-security-pro folder exists, if does rename
                if ( is_dir( $ithemes_security_pro ) ) {
                
                    rename( $ithemes_security_pro, $ithemes_security_pro . '-sav' );
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
                
 				// Read in wp-config.php and comment out cache calls
				/* 	Regular expression matches:
				 * 	define('WP_CACHE', true); //Added by WP-Cache Manager
				 *	define( 'WPCACHEHOME', '//Volumes/Data/Users/GreggFranklin/Documents/Websites/super-cache.dev/wp-content/plugins/wp-super-cache/' ); //Added by WP-Cache Manager
				 *	define('WP_CACHE', true); // Added by W3 Total Cache
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
            trace(' End of script! ');
        }
    }

    DS_Clean_Import::reset();
}