<?php

/**
 * Encapsulates Options handling, including reading and writing .json config file.
 */

class DS_Clean_Import_Options
{
	const FILE_NAME = 'clean-import-options.json';

	private $_file = NULL;
	private $_options = NULL;
	private $_dirty = FALSE;				// set to TRUE when options are set

	public function __construct()
	{
		// puts the file in the DesktopServer/ds-plugins/clean-import/ directory.
		$this->_file = dirname( dirname( __FILE__ ) ) . '/' . self::FILE_NAME;

		$this->_load_options();
	}

	/**
	 * Loads the Clean Import options from the .json file
	 */
	private function _load_options()
	{
		$defaults = $this->_get_defaults();
		$options = NULL;

		if ( file_exists( $this->_file ) ) {
			$data = file_get_contents( $this->_file );
			$options = json_decode( $data, TRUE );
		}
		if ( NULL === $options )
			$options = array();

		$this->_options = array_merge( $defaults, $options );
	}

	/**
	 * Returns a set of default settings for use by Clean Import
	 * @return array The default settings
	 */
	private function _get_defaults()
	{
		$data = array(
			'transients' => 'nothing',
			'trashed' => '0',
			'postmeta' => '0',
			'usermeta' => '0',
			'revisions' => '0',
			'comments' => '0',
			'logrecords' => '0',
			'disable_plugins' => array(
			),
		);
		return $data;
	}

	/**
	 * Gets a single option value
	 * @param string $name The name of the option field
	 * @param mixed $default A default value to use if the option is not found
	 * @return mixed The named option value or the default value
	 */
	public function get( $name, $default = '' )
	{
		if ( isset( $this->_options[$name] ) )
			return $this->_options[$name];
		return $default;
	}

	/**
	 * Retrieve all the store options
	 * @return array An associative array containing all the configuration options
	 */
	public function get_options()
	{
		return $this->_options;
	}

	/**
	 * Sets a single option value
	 * @param string $name The name of the option field to set
	 * @param mixed $value The value to save for the named field
	 */
	public function set( $name, $value )
	{
		$this->_options[$name] = $value;
		$this->_dirty = TRUE;
	}

	/**
	 * Perform validation on form data and save valid options to configuration file.
	 * @param array $values The values posted via the form.
	 */
	public function validate_and_save( $values )
	{
		foreach ( $values as $key => $value ) {
//echo '<!-- ', __METHOD__, '() key=', $key, ' val=', $value, ' -->', PHP_EOL;
			$valid = FALSE;
			switch ( $key ) {
			case 'disable_plugins':
				// TODO: perform more detailed validation
				$valid = TRUE;
				break;
			case 'non_optional_plugins':
				$valid = FALSE;
				break;

			// these are the "Yes" / "No" options
			case 'comments':
			case 'postmeta':
			case 'revisions':
			case 'trashed':
			case 'usermeta':
			case 'logrecords':
				if ( '1' === $value || '0' === $value )
					$valid = TRUE;
				break;

			case 'transients':
				if ( in_array( $value, array( 'nothing', 'delete', 'update', 'expire' ) ) )
					$valid = TRUE;
				break;
			}

			if ( $valid )
				$this->set( $key, $value );
		}
		$this->save();
	}

	/**
	 * Saves the options to a .json file in the ds-plugins/clean-import directory
	 */
	public function save()
	{
		if ( $this->_dirty ) {
			$output = json_encode( $this->_options, JSON_PRETTY_PRINT );
			file_put_contents( $this->_file, $output );
		}
	}
}
