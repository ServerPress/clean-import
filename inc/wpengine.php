<?php

/**
 * WPEngine specific actions
 *
 * @author ServerPress
 * @package clean-import
 * @since 1.0.0
 */
 
 	// Collect needed configuration info
 	$mu_plugins = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/mu-plugins';

	//Saves config
	$clean_config_file->save( $wpconfig );

  	// See if mu-plugins folder exists, if does rename
  	if ( is_dir( $mu_plugins ) ) {
		rename( $mu_plugins, $mu_plugins . '-sav-' . time() ); 
  	}