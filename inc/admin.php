<?php

/**
 * Implements configuration capabilities within the WordPress Admin
 */

class DS_Clean_Import_Admin
{
	private static $_instance = NULL;

	const SETTINGS_PAGE = 'clean-import-options';
	const SETTINGS_FIELDS = 'clean_import_group';
	const OPTION_NAME = 'clean-import';
	const SETTINGS_UPDATED_NOTICE = 'clean-import-settings-saved';

	private $_admin_page = NULL;

	private function __construct()
	{
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_configuration_page' ) );
			add_action( 'admin_init', array( $this, 'settings_api_init' ) );
		}
	}

	/**
	 * Return singleton instance of the admin class
	 * @return object Singleton reference to the class
	 */
	public static function get_instance()
	{
		if ( NULL === self::$_instance )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Adds the Clean Import option page to the Tools menu
	 * @return type
	 */
	public function add_configuration_page()
	{
		$this->_admin_page = add_management_page( __( 'DesktopServer Clean Import', 'clean-import' ),
			__( 'DS Clean Import', 'clean-import' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( $this, 'settings_page' )
		);
		add_action( 'load-' . $this->_admin_page, array( $this, 'contextual_help' ) );

		return $this->_admin_page;
	}

	public function contextual_help()
	{
		$screen = get_current_screen();
		if ( $this->_admin_page !== $screen->id )
			return;

		$screen->set_help_sidebar(
			'<p><string>' . __('For more information:', 'clean-import' ) . '</strong></p>' .
			'<p>' . sprintf( '<a href="%2$s" target="_blank">%1$s</a>%3$s<a href="%5$s" target="_blank">%4$s</a>.',
				__( 'See our documentation here: ', 'clean-import'),
				'https://serverpress.com/clean-import',
				__( ', or visit our ', 'clean-import'),
				__( 'GitHub Repository', 'clean-import' ),
				'https://github.com/ServerPress/clean-import') . '</p>'
		);

		$screen->add_help_tab( array(
			'id'		=> 'ds-clean-import',
			'title'		=> __( 'DS Clean Import', 'clean-import' ),
			'content'	=> 
				'<p><b>' . __( 'Perform Transient Cleanup Options:', 'clean-import') . '</b></p>' .
				'<p><ul><li><b>' . __( 'Do nothing with Transients', 'clean-import' ) . '</b> - '.
					__( 'Clean Import will not remove or update any Transient data in the imported site.', 'clean-import' ) . '</li>' .
					'<li><b>' . __( 'Delete all Transient data', 'clean-import') . '</b> - ' .
					__( 'Clean Import will remove all Transient data in the Imported site, whether it is expired or not.', 'clean-import' ) . '</li>' .
					'<li><b>' . __( 'Update Transient expiration', 'clean-import' ) . '</b> - ' .
					__( 'Clean Import will update the expiration time of all Transients, setting it to 24 hours after the time of Import.', 'clean-import' ) . '</li>' .
					'<li><b>' . __( 'Remove old Transients and update remaining', 'clean-import' ) . '</b> - '.
					__( 'Clean Import will Remove any Transient data that was already expired at the time the Archive was created; then update the expiration time on any remaining Transients, setting it to 24 hours after the time of Import.', 'clean-import' ) . '</li>' .
					'</ul></p>' .
				'<p><b>' . __( 'Remove Options', 'clean-import' ) . '</b> - ' .
					__( 'These options control whether or not Clean Import will remove Trashed/Deleted posts, Post Revisions, Trashed/Deleted Comments and Orphaned postmeta or usermeta (post or user meta data that does not have an existing Post or User associated with it.). When these options are set to "Yes", the data in question will be removed. When these are set to "No", no data will be removed. The data is only removed from the database after the Import is performed. The original saved database is not affected. Because of this, Importing the same archive at a later time, with different options, will yield different results.', 'clean-import' ) . '</p>' .
				'<p><b>' . __( 'Disable Plugins after Import', 'clean-import' ) . '</b> - '.
					__( 'These options allow you to select which plugins will be disabled as part of the Import process. Plugins are disabled by renaming the plugin file or the plugin directory. Once imported, these can be renamed back to their original name and enabled within the WordPress Dashboard.', 'clean-import' ) . '</p>' .
				'<p><b>' . __( 'Disabled Plugins', 'clean-import' ) . '</b> - ' .
					__( 'These plugins are always disabled after the Import process. This is done because these plugins are known to cause interferance with local hosting environments. While you can rename these plugins and enable them after the Import has completed, we do not recomment this as it can cause problems with the site hosted under DesktopServer. This list is provided for your information only; you are not given a choice as to whether this will be disabled or not. These will always be disabled.', 'clean-import' ) . '</p>'
		));
		
		do_action( 'clean-import-options_contextual_help', $screen );
	}

	/**
	 * Initializes the Settings API and adds the fields for the settings page
	 */
	public function settings_api_init()
	{
		require_once( dirname( __FILE__ ) . '/options.php' );
		$option_values = new DS_Clean_Import_Options();

		$section_id = 'clean_import';

		register_setting(
			self::SETTINGS_FIELDS,							// option group, used for settings_fields()
			self::OPTION_NAME,								// option name, used as key in database
			array( $this, 'validate_settings' )
		);

		add_settings_section(
			$section_id,									// section id
			__( 'DS Clean Import', 'clean-import' ),		// title
			'__return_true',								// callback
			self::SETTINGS_PAGE								// option page
		);

		add_settings_field(
			'transients',									// field id
			__( 'Perform Transient cleanup:', 'clean-import' ),	// title
			array( $this, 'render_radio_field' ),			// callback
			self::SETTINGS_PAGE,							// page
			$section_id,									// section id
			array(
				'name' => 'transients',
				'value' => $option_values->get( 'transients', 'nothing' ),
				'description' => __( 'How Transients will be handled after import has completed. See Help screen above for detailed explanation of options.', 'clean-import' ),
				'options' => array(
					'nothing' => __( 'Do nothing with Transients', 'clean-import' ),
					'delete' => __( 'Delete all Transient data', 'clean-import' ),
					'update' => __( 'Update Transient expiration', 'clean-import' ),
					'expire' => __( 'Remove old Transients and update remaining', 'clean-import' ),
				)
			)
		);

		$yes_no = array(
			'1' => __( 'Yes', 'clean-import' ),
			'0' => __( 'No', 'clean-import' ),
		);

		add_settings_field(
			'trashed',											// field id
			__( 'Remove Trashed posts:', 'clean-import' ),
			array( $this, 'render_radio_field' ),
			self::SETTINGS_PAGE,
			$section_id,
			array(
				'name' => 'trashed',
				'value' => $option_values->get( 'trashed', '0' ),
				'description' => __( 'If "Yes", Clean Import will removed any Posts marked as Trashed, as well as any associated postmeta data.', 'clean-import' ),
				'options' => $yes_no,
			)
		);
	
		add_settings_field(
			'revisions',										// field id
			__( 'Remove Post Revisions:', 'clean-import' ),
			array( $this, 'render_radio_field' ),
			self::SETTINGS_PAGE,
			$section_id,
			array(
				'name' => 'revisions',
				'value' => $option_values->get( 'revisions', '0' ),
				'description' => __( 'If "Yes", Clean Import will remove any Post Revisions from the Posts table, as well as any associated postmeta data.', 'clean-import' ),
				'options' => $yes_no,
			)
		);
		add_settings_field(
			'comments',											// field id
			__( 'Remove deleted Comments:', 'clean-import' ),
			array( $this, 'render_radio_field' ),
			self::SETTINGS_PAGE,
			$section_id,
			array(
				'name' => 'comments',
				'value' => $option_values->get( 'comments', '0' ),
				'description' => __( 'If "Yes", Clean Import will remove any deleted Comments and associated metadata.', 'clean-import' ),
				'options' => $yes_no,
			)
		);

		add_settings_field(
			'postmeta',											// field id
			__( 'Remove orphaned postmeta:', 'clean-import' ),
			array( $this, 'render_radio_field' ),
			self::SETTINGS_PAGE,
			$section_id,
			array(
				'name' => 'postmeta',
				'value' => $option_values->get( 'postmeta', '0' ),
				'description' => __( 'If "Yes", Clean Import will remove any postmeta records that do not have a corresponding record in the Posts table.', 'clean-import' ),
				'options' => $yes_no,
			)
		);

		add_settings_field(
			'usermeta',
			__( 'Remove orphaned usermeta:', 'clean-import' ),
			array( $this, 'render_radio_field' ),
			self::SETTINGS_PAGE,
			$section_id,
			array(
				'name' => 'usermeta',
				'value' => $option_values->get( 'usermeta', '0' ),
				'description' => __( 'If "Yes", Clean Import will remove any usermeta records that do not have a corresponding record in the Users table.', 'clean-import' ),
				'options' => $yes_no,
			)
		);

		add_settings_field(
			'logrecords',
			__( 'Skip importing log records:', 'clean-import' ),
			array( $this, 'render_radio_field' ),
			self::SETTINGS_PAGE,
			$section_id,
			array(
				'name' => 'logrecords',
				'value' => $option_values->get( 'logrecords', '0' ),
				'description' => __( 'If "Yes", Clean Import will not import log records from plugins such as Gravity Forms, iThemes, Pretty Links, Wordfence, etc.', 'clean-import' ),
				'options' => $yes_no,
			)
		);

		// load support classes
		require_once( dirname( __FILE__ ) . '/base.php' );
		require_once( dirname( __FILE__ ) . '/plugins.php' );
		$plugins = new DS_Clean_Import_Plugins();
		$optional = $plugins->get_optional_plugins();
		$plugin_list = array();
		foreach ( $optional as $name )
			$plugin_list[$name] = '1';

		add_settings_field(
			'disable_plugins',								// field id
			__( 'Disable specific plugins after Import:', 'clean-import' ),
			array( $this, 'render_checkbox_field' ),		// callback
			self::SETTINGS_PAGE,							// page
			$section_id,									// section id
			array(
				'name' => 'disable_plugins',
				'value' => $option_values->get( 'disable_plugins', $plugin_list ),
				'description' => __( 'Checked plugins will be disabled (renamed) after your site is Imported into DesktopServer.', 'clean-import' ),
				'options' => $plugin_list,
			)
		);

		$non_optional = $plugins->get_non_optional_plugins();
		$plugin_list = array();
		foreach ( $non_optional as $name )
			$plugin_list[$name] = '1';
		add_settings_field(
			'non_optional_plugins',								// field id
			__( 'Disabled plugins:', 'clean-import' ),
			array( $this, 'render_checkbox_field' ),		// callback
			self::SETTINGS_PAGE,							// page
			$section_id,									// section id
			array(
				'name' => 'non_optional_plugins',
				'value' => '0',
				'description' => __( 'These plugins will always be diabled upon Import to DesktopServer due to conflicts with the local environment.', 'clean-import' ),
				'options' => $plugin_list,
				'disabled' => TRUE,
			)
		);

		// handle display of "Settings Saved" notice
		$key = self::SETTINGS_UPDATED_NOTICE . get_current_user_id();
		if ( FALSE !== $msg = get_transient( $key ) ) {
			delete_transient( $key );
			add_action( 'admin_notices', array( $this, 'show_settings_saved' ) );
		}
	}
	public function show_settings_saved()
	{
?>
		<div class="notice notice-success is-dismissible">
	        <p><?php _e( 'Your Clean Import changes have been saved and will be used on all future Imports.', 'clean-import' ); ?></p>
	    </div>
<?php
	}

	/**
	 * Callback for the settings page
	 */
	public function settings_page()
	{
		echo '<div class="wrap ds-clean-import-settings">';
		echo '<h2>', __( 'DesktopServer Clean Import Tool - Configuration Settings', 'clean-import' ), '</h2>';

		echo '<p>', __('Please note: these settings will be shared and used across <em>all</em> of your DesktopServer sites. Making changes here will affect all future Import operations, no matter what site is being Imported.', 'clean-import' ), '</p>';

		echo '<form id="ds-clean-import-form" action="options.php" method="POST">';
		settings_fields( self::SETTINGS_FIELDS );
		do_settings_sections( self::SETTINGS_PAGE );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Callback for validating settings. Called from the Settings API
	 * @param array $values An associative array of the form fields being saved.
	 */
	public function validate_settings( $values )
	{
		if ( ! current_user_can( 'manage_options' ) )
			return NULL;

		require_once( dirname( __FILE__ ) . '/options.php' );
		$options = new DS_Clean_Import_Options();
		$options->validate_and_save( $values );

		// block the settings from being written to the database
		add_filter( 'pre_update_option', array( $this, 'block_update_option' ), 10, 3 );
		// signal display of settings saved message
		set_transient( self::SETTINGS_UPDATED_NOTICE . get_current_user_id(), '' );

		return NULL;
	}

	/**
	 * Callback for the 'pre_update_option' filter. Used to block saving options in the options table
	 * @param mixed $value The value being saved
	 * @param string $option The option name being saved
	 * @param mixed $old_value The old value before the update
	 * @return mixed The value to be saved in the option.
	 */
	public function block_update_option( $value, $option, $old_value )
	{
		if ( self::OPTION_NAME === $option ) {
			// if it's our option name, set the option to the $old_value.
			// This has the effect of making update_option() not write anything to the database.
			$value = $old_value;
		}
		return $value;
	}

	/**
	 * Renders a set of Radio Button input fields
	 * @param array $args Array of field specific data used in rendering the Radio Buttons
	 */
	public function render_radio_field( $args )
	{
		$options = $args['options'];
		$name = $args['name'];

		foreach ( $options as $value => $label ) {
			// clean_import_settings
			printf('<input type="radio" name="%s[%s]" value="%s" %s /> %s<br/>',
				self::OPTION_NAME, $name, $value, checked( $value, $args['value'], FALSE ), $label );
		}
		if ( isset( $args['description'] ) )
			echo '<em>', esc_html( $args['description'] ), '</em>';
	}

	/**
	 * Renders a set of Checkboxe input fields
	 * @param array $args Array of field specific data used in rendering the Checkboxes
	 */
	public function render_checkbox_field( $args )
	{
		$options = $args['options'];
		$name = $args['name'];
		$defaults = $args['value'];
		$disabled = ( isset( $args['disabled'] ) && $args['disabled'] ) ? TRUE : FALSE;

		foreach ( $options as $key => $value ) {
			if ( ! $disabled )
				printf('<input type="hidden" name="%s[%s][%s]" value="0" />',
					self::OPTION_NAME, $name, $key );
			if ( $disabled )
				printf('<input type="checkbox" name="%s[%s][%s]" value="0" checked="checked" disabled="disabled" /> %s<br/>',
					self::OPTION_NAME, $name, $key, $key );
			else
				printf('<input type="checkbox" name="%s[%s][%s]" value="1" %s /> %s<br/>',
					self::OPTION_NAME, $name, $key, checked( $value, isset( $defaults[$key] ) ? $defaults[$key] : $value, FALSE ), $key );
		}
		if ( isset( $args['description'] ) )
			echo '<em>', esc_html( $args['description'] ), '</em>';
	}
}

DS_Clean_Import_Admin::get_instance();

// EOF