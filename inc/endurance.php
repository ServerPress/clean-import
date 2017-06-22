<?php
/**
 * Endurance specific actions
 *
 * @author ServerPress
 * @package clean-import
 * @since 1.0.0
 */
 /*
 	// Collect needed configuration info
 	$mu_path = $ds_runtime->preferences->sites->{$siteName}->sitePath . '/wp-content/mu-plugins';
 	$endurance_browser_cache = $mu_path . '/endurance-browser-cache.php';
 	$endurance_page_cache = $mu_path . '/endurance-page-cache.php';
 	$endurance_php_edge = $mu_path . '/endurance-php-edge.php';
 	$endurance_sso = $mu_path . '/sso.php';

	//Rename cache, security files and folders
	$files_to_rename = array(
		$advanced_cache,
		$endurance_browser_cache,
		$endurance_page_cache,
		$endurance_php_edge,
		$endurance_sso
	);

	foreach	( $files_to_rename as $file_to_rename ) {
		if ( file_exists( $file_to_rename ) ) {
			rename( $file_to_rename, $file_to_rename . '-sav-' . time() );
		}
	} 
*/

class DS_Clean_Import_Endurance extends DS_Clean_Import_Base
{
	public function clean_import()
	{
		// collect needed configuration info
		$mu_path = $this->install_path . 'wp-content/mu-plugins/';

		// rename cache, security files and folders
		$files_to_rename = array(
			$mu_path . 'endurance-browser-cache.php',
			$mu_path . 'endurance-page-cache.php',
			$mu_path . 'endurance-php-edge.php',
			$mu_path . 'sso.php',
		);

		foreach	( $files_to_rename as $file_to_rename ) {
			if ( file_exists( $file_to_rename ) ) {
				rename( $file_to_rename, $file_to_rename . $this->backup_ext );
			}
		}
	}
}
