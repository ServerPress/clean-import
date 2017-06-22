<?php
/**
 * WPEngine specific actions
 *
 * @author ServerPress
 * @package clean-import
 * @since 1.0.0
 */

/* 	// Collect needed configuration info
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
*/

		// Move and rename database
/*
		trace('Start!');
		$database = $sitePath . '/wp-content/mysql.sql';
		trace($database);
		$new_database = $sitePath . '/database.sql';
		trace($new_databas);
		if ( file_exists ( $database ) ) {
			rename($database,$new_database);
			trace('rename mysql.sql to database.sql');
			unlink($database);
			trace('Delete mysql.sql');
		}
*/

class DS_Clean_Import_WPEngine extends DS_Clean_Import_Base
{
	public function clean_import()
	{
DS_Clean_Import::debug(__METHOD__.'()');
		// collect needed configuration info
		$mu_path = $this->install_path . 'wp-content/mu-plugins/';

		// rename cache, security files and folders
		$files_to_rename = array(
			$mu_path . 'force-strong-passwords',
			$mu_path . 'wpengine-common',
			$mu_path . 'mu-plugin.php',
			$mu_path . 'slt-force-strong-passwords.php',
			$mu_path . 'stop-long-comments.php',
			$mu_path . 'td-auto-excerpt.php',
			$mu_path . 'td-default-migrations.php',
			$mu_path . 'td-environment.php',
			$mu_path . 'td-options-log.php',
			$mu_path . 'wpengine-hide.php',
		);

		$this->rename_files($files_to_rename);
	}
}
