<?php

// display the Clean Import page as a localhost extension

global $ds_runtime;

// Bail if not localhost
if( ! $ds_runtime->is_localhost ) {
	return;
}

require_once( dirname( __FILE__ ) . '/inc/options.php' );
$options = new DS_Clean_Import_Options();
$settings = $options->get_options();
$saved = FALSE;
//var_export($settings);

if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['clean-import'] ) ) {
	// perform validation
	$values = $_POST['clean-import'];
	$options->validate_and_save( $values );
	$saved = TRUE;
	$settings = $options->get_options();
}

define( 'DS_CLEAN_IMPORT_VER', '1.1' );

$ds_runtime->add_action( 'ds_head', 'ds_clean_import_head' );
function ds_clean_import_head()
{
	// inject our css into the header stuff
	echo '<link href="http://localhost/css/bootstrap.min.css" rel="stylesheet"/>', PHP_EOL;
//	echo '<link href="http://localhost/ds-plugins/mailbox-viewer/css/jquery.splitter.css" rel="stylesheet"/>', PHP_EOL;
	echo '<link href="http://localhost/ds-plugins/clean-import/css/clean-import.css?v=1" rel="stylesheet"/>', PHP_EOL;
}

function ds_clean_import_radio( $name, $title, $value, $options, $description )
{
	echo '<tr>';
	echo	'<td>', $title, '</td>';
	echo	'<td>';
	foreach ( $options as $key => $desc ) {
		echo '<input type="radio" name="clean-import[', $name, ']" value="', $key, '" ', ( $value == $key ? ' checked="checked" ' : ''), ' /> ', $desc, '<br/>';
	}
	echo		'<p>', $description, '</p>';
	echo	'</td>';
	echo '</tr>';
}

function ds_clean_import_checkbox( $name, $title, $values, $options, $description, $disabled = FALSE )
{
	echo '<tr>';
	echo	'<td>', $title, '</td>';
	echo	'<td>';
//echo var_export($values, TRUE), '<br/>';
	foreach ( $options as $key => $value ) {
		if ( ! $disabled )
			printf('<input type="hidden" name="clean-import[%s][%s]" value="0" />',
				$name, $key );
		if ( $disabled )
			printf('<input type="checkbox" name="clean-import[%s][%s]" value="0" checked="checked" disabled="disabled" /> %s<br/>',
				$name, $key, $key );
		else
			printf('<input type="checkbox" name="clean-import[%s][%s]" value="1" %s /> %s<br/>',
				$name, $key, ( ! isset( $values[$key] ) || $value === $values[$key] ? ' checked="checked" ' : '' ), $key );
	}
	echo '<em>', $description, '</em>';
	echo	'</td>';
	echo '</tr>';
}

include_once( $ds_runtime->htdocs_dir . '/header.php');

$ch = curl_init();
curl_setopt( $ch, CURLOPT_URL, 'https://raw.githubusercontent.com/ServerPress/clean-import/master/clean-import.php' );
curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
$buffer = curl_exec( $ch );
curl_close( $ch );

if( $buffer ) {
	$buffer = explode( "\n", $buffer );
	$latest = str_replace( ' * Version: ', '', $buffer[5] );
	$current = DS_CLEAN_IMPORT_VER;
}
?>
	<div class="container">
		<div id="contextual-help" style="display:none">
			<p><b>Perform Transient Cleanup Options:</b></p>
			<p><ul>
				<li><b>Do nothing with Transients</b> - Clean Import will not remove or update any Transient data in the imported site.</li>
				<li><b>Delete all Transient data</b> - Clean Import will remove all Transient data in the Imported site, whether it is expired or not.</li>
				<li><b>Update Transient expiration</b> - Clean Import will update the expiration time of all Transients, setting it to 24 hours after the time of Import.</li>
				<li><b>Remove old Transients and update remaining</b> - Clean Import will Remove any Transient data that was already expired at the time the Archive was created; then update the expiration time on any remaining Transients, setting it to 24 hours after the time of Import.</li>
			</ul><p>
			<p><b>Remove Options</b> - These options control whether or not Clean Import will remove Trashed/Deleted posts, Post Revisions, Trashed/Deleted Comments and Orphaned postmeta or usermeta (post or user meta data that does not have an existing Post or User associated with it.). When these options are set to "Yes", the data in question will be removed. When these are set to "No", no data will be removed. The data is only removed from the database after the Import is performed. The original saved database is not affected. Because of this, Importing the same archive at a later time, with different options, will yield different results.</p>
			<p><b>Disable Plugins after Import</b> - These options allow you to select which plugins will be disabled as part of the Import process. Plugins are disabled by renaming the plugin file or the plugin directory. Once imported, these can be renamed back to their original name and enabled within the WordPress Dashboard.</p>
			<p><b>Disabled Plugins</b> - These plugins are always disabled after the Import process. This is done because these plugins are known to cause interferance with local hosting environments. While you can rename these plugins and enable them after the Import has completed, we do not recomment this as it can cause problems with the site hosted under DesktopServer. This list is provided for your information only; you are not given a choice as to whether this will be disabled or not. These will always be disabled.</p>
		</div>
		<button id="contextual-help-button" type="button" style="float:right" onclick="javascript:contextual_help_button(); return false;">Help v</button>
<?php	if ( $buffer && version_compare( $current, $latest, '<' ) ) { ?>
			<div class="outdated">
				<h4>Notice!</h4>
				<p>An update is available for the Desktop Server Clean Import Tool!&nbsp;&nbsp;&nbsp;<strong>Your Version:</strong> <code><?php echo $current; ?></code>&nbsp;&nbsp;&nbsp;<strong>Latest:</strong> <code><?php echo $latest; ?></code></p>
				<a href="https://github.com/ServerPress/clean-import/archive/<?php echo $latest; ?>.zip">Download the latest version</a>
			</div>
<?php	} ?>

<?php /*
		<div class="btn-group btn-group-sm" role="group">
			<button type="button" class="btn btn-default" id="btn-refresh">Refresh</button>
		</div>
		<div class="btn-group btn-group-sm" role="group">
			<button type="button" class="btn btn-default" id="btn-empty">Empty All</button>
		</div>
		<p></p>
*/ ?>

<?php
require_once( dirname( __FILE__ ) . '/inc/base.php' );
require_once( dirname( __FILE__ ) . '/inc/plugins.php' );
?>
		<div id="clean-import">
			<div class="list">
				<h2>DesktopServer&trade; Clean Import Settings</h2>
<?php
				if ( $saved ) {
					echo '<div class="saved-notice">Your changes have been saved.</div>';
				}
?>
				<form id="clean-import-settings" action="http://localhost/ds-plugins/clean-import/page.php" method="post">
					<table id="settings-list" class="table clean-import">
<?php /*				<thead>
						<tr>
							<th id="mail-date">Date:</th>
							<th id="mail-to">To:</th>
							<th id="mail-from">From:</th>
							<th id="mail-subject">Subject:</th>
						</tr>
						</thead> */ ?>

						<tbody>
<?php
						$trans = array(
							'nothing' => 'Do nothing with Transients',
							'delete' => 'Delete all Transient data',
							'update' => 'Update Transient expiration',
							'expire' => 'Remove old Transients and update remaining',
						);
						ds_clean_import_radio( 'transients', 'Perform Transient cleanup:', $settings['transients'], $trans, 'How Transients will be handled after import has completed. See Help screen above for detailed explanation of options.' );

						$yes_no = array(
							'1' => 'Yes',
							'0' => 'No',
						);

						ds_clean_import_radio( 'trashed', 'Remove Trashed posts:', $settings['trashed'], $yes_no, 'If "Yes", Clean Import will removed any Posts marked as Trashed, as well as any associated postmeta data.' );
						ds_clean_import_radio( 'revisions', 'Remove Post Revisions:', $settings['revisions'], $yes_no, 'If "Yes", Clean Import will remove any Post Revisions from the Posts table, as well as any associated postmeta data.' );
						ds_clean_import_radio( 'comments', 'Remove deleted Comments:', $settings['comments'], $yes_no, 'If "Yes", Clean Import will remove any deleted Comments and associated metadata.' );
						ds_clean_import_radio( 'postmeta', 'Remove orphaned postmeta:', $settings['postmeta'], $yes_no, 'If "Yes", Clean Import will remove any postmeta records that do not have a corresponding record in the Posts table.' );
						ds_clean_import_radio( 'usermeta', 'Remove orphaned usermeta:', $settings['usermeta'], $yes_no, 'If "Yes", Clean Import will remove any usermeta records that do not have a corresponding record in the Users table.' );

						$plugins = new DS_Clean_Import_Plugins();
						$optional = $plugins->get_optional_plugins();
						$plugin_list = array();
						foreach ( $optional as $name )
							$plugin_list[$name] = '1';

						ds_clean_import_checkbox( 'disable_plugins', 'Disable specific plugins after Import:', $settings['disable_plugins'], $plugin_list, 'Checked plugins will be disabled (renamed) after your site is Imported into DesktopServer.' );

						$non_optional = $plugins->get_non_optional_plugins();
						$plugin_list = array();
						foreach ( $non_optional as $name )
							$plugin_list[$name] = '1';

						ds_clean_import_checkbox( 'non_optional_plugins', 'Disabled plugins:', '0', $plugin_list, 'These plugins will always be disabled upon Import to DesktopServer due to conflicts with the local environment.', TRUE )
?>

						<tr>
							<td>&nbsp;</td>
							<td>
								<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
							</td>
						</tr>
						</tbody>
					</table>
				</form>
			</div><!-- .list -->
		</div><!-- #clean-import -->
	</div><!-- .container -->
<?php

include_once( 'footer.php' );
