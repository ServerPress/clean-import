<?php
global $ds_runtime;

if ( !$ds_runtime->is_localhost ) return;
if ( $ds_runtime->last_ui_event === false ) return;
if ( $ds_runtime->last_ui_event->action !== "site_imported" ) return;

include_once( $ds_runtime->htdocs_dir . '/classes/string.php' );
include_once( $ds_runtime->htdocs_dir . '/classes/class-ds-utils.php' );
include_once( $ds_runtime->htdocs_dir . '/classes/class-ds-config-file.php' );

// Implement Reset Flywheel
if ( ! class_exists( 'DS_Reset_Flywheel' ) ) {

    class DS_Reset_Flywheel {
        static function reset() {
        	global $ds_runtime;
        	
            $siteName = $ds_runtime->last_ui_event->info[0];
            $mu_plugins = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/mu-plugins';
            $wpconfig = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-config.php';
            $wpsettings = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-settings.php';

            // Collect needed configuration info
            $flywheel_config_file = new DS_ConfigFile( $wpconfig );
            $db_user = $flywheel_config_file->get_key( 'DB_USER' );
            $db_password = $flywheel_config_file->get_key( 'DB_PASSWORD' );
            
            //Collect Multisite configutation info
            $wp_allow_multisite = $flywheel_config_file->get_key( 'WP_ALLOW_MULTISITE' );
            $multisite = $flywheel_config_file->get_key( 'MULTISITE' );
            $subdomain_install = $flywheel_config_file->get_key( 'SUBDOMAIN_INSTALL' );
            $domain_current_site = $flywheel_config_file->get_key( 'DOMAIN_CURRENT_SITE' );
            $path_current_site = $flywheel_config_file->get_key( 'PATH_CURRENT_SITE' );
            $site_id_current_site = $flywheel_config_file->get_key( 'SITE_ID_CURRENT_SITE' );
            $blog_id_current_site = $flywheel_config_file->get_key( 'BLOG_ID_CURRENT_SITE' );

            $is_flywheel_config_file = false;
            $flywheel_constants = array(
                'FLYWHEEL_PLUGIN_DIR',
            );
            foreach ($flywheel_constants as $constant ) {
                $config_value = $flywheel_config_file->get_key( $constant );
                if ( !empty( $config_value ) ) {
                    $is_flywheel_config_file = true;
                }
            }
            if ( !$is_flywheel_config_file ) {
                return;
            }

            $flywheel_config_file->set_type( 'php-variable' );
            $table_prefix = $flywheel_config_file->get_key( 'table_prefix' );

            // Backup the config file
            if ( file_exists( $wpconfig ) ) {
                copy( $wpconfig, $wpconfig . '-sav' );
            }

            // Set the configuration info in the new wp-config
            $source = dirname( __FILE__ ) . '/lib/wp-config-sample.php';
            $clean_config_file = new DS_ConfigFile( $source );
            $clean_config_file->set_key( 'DB_USER', $db_user );
            $clean_config_file->set_key( 'DB_NAME', $db_user );
            $clean_config_file->set_key( 'DB_PASSWORD', $db_password );
	    	$clean_config_file->set_key( 'DB_HOST', '127.0.0.1' );

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


            $clean_config_file->save( $wpconfig );
            
            // Reset the wp-settings.php file

            // Backup the settings file
            if ( file_exists( $wpsettings ) ) {
                copy( $wpsettings, $wpsettings . '-sav' );
            }
            
            $source2 = dirname( __FILE__ ) . '/lib/wp-settings-sample.php';
            $clean_settings_file = new DS_ConfigFile( $source2 );
            
            $clean_settings_file->save( $wpsettings );

            // See if mu-plugins folder exists, if does rename
            if ( is_dir( $mu_plugins ) ) {

                rename( $mu_plugins, $mu_plugins . '-sav' );
                
            }
        }
    }
    DS_Reset_Flywheel::reset();
}

