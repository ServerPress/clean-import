<?php

/**
 * Create a menu item within our localhost tools pull down menu.
 */
global $ds_runtime;

if ( ! $ds_runtime->is_localhost )
	return;							// not localhost
if ( FALSE !== $ds_runtime->last_ui_event )
	return;							// not interested in events

// add menu to the localhost page
$ds_runtime->add_action( 'append_tools_menu', 'ds_clean_import_tools_menu' );
function ds_clean_import_tools_menu()
{
	echo '<li><a href="http://localhost/ds-plugins/clean-import/page.php">Clean Import Settings</a></li>';
}
