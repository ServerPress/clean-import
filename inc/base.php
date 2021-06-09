<?php

/**
 * Base class for all of the Clean Import operations
 */

class DS_Clean_Import_Base
{
	public $site_name = NULL;
	public $site_path = NULL;
	public $install_path = NULL;
	public $backup_ext = NULL;

	public $config_file = NULL;				// original config file
	public $new_config = NULL;				// new config file

	/**
	 * Copies the properties from the DS_Clean_Import class into the child class
	 */
	public function __construct()
	{
		$ci = DS_Clean_Import::$instance;
		if ( NULL !== $ci ) {
			// TODO: use introspection
			$this->site_name = $ci->site_name;
			$this->site_path = $ci->site_path;
			$this->install_path = $ci->install_path;
			$this->backup_ext = $ci->backup_ext;

			$this->config_file = $ci->config_file;
			$this->new_config = $ci->new_config;
		}
	}


	/**
	 * Renames a list of files
	 * @param array $file_list The files and/or directories to rename
	 */
	public function rename_files( $file_list )
	{
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' has ' . count($file_list) . ' files to rename:');
		foreach	( $file_list as $file_to_rename ) {
			if ( file_exists( $file_to_rename ) ) {
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renaming "' . $file_to_rename . '" to "' . $file_to_rename . $this->backup_ext . '"');
				rename( $file_to_rename, $file_to_rename . $this->backup_ext );
			} else {
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' file "' . $file_to_rename . '" not found');
			}
		}
	}
}
