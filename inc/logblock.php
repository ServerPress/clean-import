<?php

class DS_Clean_Import_LogBlock extends DS_Clean_Import_Base
{
	private $options = NULL;

	private $tables = array(
		'prli_clicks',					// Pretty Links clicke
		'itsec_distributed_storage',	// iThemes Security log tables
		'itsec_lockouts',
		'itsec_log',
		'itsec_logs',
		'itsec_temp',
		'rg_form_view',					// Gravity Forms views
		'tve_leads_event_log',			// Thrive Events leads
		'wfFileMods',					// WordFence file modifications
		'wfHoover',						// WordFence bad site checks
	);

	public function pre_import_process( $info )
	{
DS_Clean_Import::debug(__METHOD__.'():' . var_export($info, TRUE));
		$this->load_options();
		// if not configured to removed log records, exit
		if ( '0' === $this->options['logrecords'] )
			return;

		$workdir = $info[2];

		// get table prefix from config file
		if ( ! file_exists( $workdir . 'wp-config.php' ) ) {
DS_Clean_Import::debug(__METHOD__.'(): wp-config cannot be found');
			return;						// abort if config file not present
		}
copy( $workdir . 'wp-config.php', 'c:\\temp\\cidebug\\wp-config.php');

		$config_file = new DS_ConfigFile( $workdir . 'wp-config.php' );
		$config_file->set_type( 'php-variable' );
		$prefix = $config_file->get_key( 'table_prefix' );
DS_Clean_Import::debug(__METHOD__.'(): database prefix: ' . $prefix);
$config_file->set_type( 'php-define' );
$db_user = $config_file->get_key( 'DB_USER' );
DS_Clean_Import::debug(__METHOD__.'(): db user: ' . $db_user );

		if ( empty( $prefix ) ) {
DS_Clean_Import::debug(__METHOD__.'(): unable to locate prefix');
			return;						// abort if prefix cannot be established
		}

		if ( 'Darwin' === PHP_OS )
			$cmd = 'sed ';
		else
			$cmd = 'C:\\xampplite\\ds-plugins\\ds-cli\\platform\\win32\\cygwin\\bin\\sed.exe ';
		$change_list = array();
		foreach ($this->tables as $table) {
			$change_list[] = 's/INSERT INTO \x60' . $prefix . $table . '\x60/-- INSERT INTO \x60' . $prefix . $table . '\x60/g';
		}
		$params = '"' . implode(';', $change_list) . '"';

DS_Clean_Import::debug(__METHOD__.'():' . $cmd . $params);

		// scan for .sql files to be updated
		$files = scandir( $workdir );
		$count = 0;
DS_Clean_Import::debug(__METHOD__.'(): found ' . count($files) . ' files');
		foreach ($files as $file) {
DS_Clean_Import::debug(__METHOD__.'(): checking file: ' . $file);
			if ( '.sql' === substr( $file, -4 ) ) {
				$workfile = $workdir . $file;
copy($workfile, 'c:\\temp\\cidebug\\' . $file . 'b');
				$exec = $cmd . $params . ' ' . $workfile . ' >' . $workfile . '-out';
DS_Clean_Import::debug(__METHOD__.'(): exec: ' . $exec);
				$res = shell_exec( $exec );
DS_Clean_Import::debug(__METHOD__.'(): res: ' . $res);
				rename( $workfile . '-out', $workfile );
				++$count;

copy($workfile, 'c:\\temp\\cidebug\\' . $file . 'a');
			}
		}
DS_Clean_Import::debug(__METHOD__.'(): modified ' . $count . ' files');
	}

	/**
	 * Loads the options via the DS_Clean_Import_Options class
	 */
	private function load_options()
	{
		// TODO: move into base class
		require_once( dirname( __FILE__ ) . '/options.php' );
		$options = new DS_Clean_Import_Options();
		$this->options = $options->get_options();
	}
}
