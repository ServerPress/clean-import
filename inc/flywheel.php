<?php

/**
 * Flywheel specific actions
 *
 * @author ServerPress
 * @package clean-import
 * @since 1.0.0
 */
/*
	// Path to wp-settings.php
	$wpsettings = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-settings.php';

	// Backup wp-settings.php
 	if ( file_exists( $wpsettings ) ) {
		copy( $wpsettings, $wpsettings . '-sav-' . time() );
	}

	$settings_source = dirname( dirname( __FILE__ ) ) . '/lib/wp-settings-sample.php';
	$clean_settings_file = new DS_ConfigFile( $settings_source );
			
	// Save new wp-settings.php
	$clean_settings_file->save( $wpsettings );
*/

class DS_Clean_Import_Flywheel extends DS_Clean_Import_Base
{
	public function clean_import()
	{
		// path to wp-settings.php
		$wpsettings = $this->install_path . 'wp-settings.php';

		// backup wp-settings.php
		if ( file_exists( $wpsettings ) ) {
			copy( $wpsettings, $wpsettings . $this->backup_ext );
		}

		// copy a known clean wp-settings.php file into the install directory
		$settings_source = dirname( dirname( __FILE__ ) ) . '/lib/wp-settings-sample.php';
		$clean_settings_file = new DS_ConfigFile( $settings_source );

		// save new wp-settings.php
		$clean_settings_file->save( $wpsettings );
	}
}
