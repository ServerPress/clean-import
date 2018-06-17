<?php
/*
Plugin Name: Clean Import
Plugin URI: http://serverpress.com/plugins/clean-import
Description: Resets .htacces, clears cache, disables plugins and resets WordPress environment for local hosting with DesktopServer.
Author: ServerPress Team
Version: 1.1.2
Text Domin: clean-import
Author URI: http://serverpress.com/
Text Domain: clean-import
*/

if ( FALSE === stripos( __DIR__, 'ds-plugins' ) ) {
	// detect if not in the ds-plugins folder
	if ( is_admin() )
		add_action( 'admin_notices', 'clean_import_install_message' );
	return;		// do not initialize the rest of the plugin
}

/**
 * Display admin notification to install plugin in correct directory
 */
function clean_import_install_message()
{
	if ( 'Darwin' === PHP_OS )
		$correct_dir = '/Applications/XAMPP/ds-plugins/';		// mac directory
	else
		$correct_dir = 'C:\\xampplite\\ds-plugins\\';			// Windows directory

	echo '<div class="notice notice-error">',
		'<p>',
		sprintf( __('<b>Notice:</b> The Clean Import plugin needs to be installed in Desktop Server\'s ds-plugins directory.<br/>Please install in %1$sclean-import', 'clean-import' ),
			$correct_dir),
		'</p>',
		'</div>';
}
