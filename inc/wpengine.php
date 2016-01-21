<?php

/**
 * WPEngine specific actions
 *
 * @author ServerPress
 * @package clean-import
 * @since 1.0.0
 */
 
 	// Collect needed configuration info
 	$mu_path = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/mu-plugins';
 	$force_strong_passwords_folder = $mu_path . '/force-strong-passwords';
 	$force_strong_passwords_script = $mu_path . '/slt-force-strong-passwords.php';
 	$wpengine_common = $mu_path . '/wpengine-common';
 	$mu_plugin_script = $mu_path . '/mu-plugin.php';
 	$stop_long_comments_script = $mu_path . '/stop-long-comments.php';


    //Rename cache, security files and folders
    $files_to_rename = array(
    	$advanced_cache,
			$force_strong_passwords_folder,
			$force_strong_passwords_script,
			$wpengine_common,
			$mu_plugin_script,
			$stop_long_comments_script
    	);
            
        foreach	( $files_to_rename as $file_to_rename ) {
			if ( file_exists( $file_to_rename ) ) {
				rename( $file_to_rename, $file_to_rename . '-sav-' . time() );  
			}
        } 