<?php

class DS_Clean_Import_Duplicator extends DS_Clean_Import_Base
{
	public function pre_import_process( $info )
	{
DS_Clean_Import::debug(__METHOD__.'():' . var_export($info, TRUE));
		$workdir = $info[2];

		if ( file_exists( $workdir . 'wp-config.php' ) )
DS_Clean_Import::debug(__METHOD__.'(): WARNING: wp-config.php already exists');

		// scan for dup-wp-config-arc__*.txt files
		$files = scandir( $workdir );
DS_Clean_Import::debug(__METHOD__.'(): found ' . count($files) . ' files');
		foreach ($files as $file) {
DS_Clean_Import::debug(__METHOD__.'(): checking file: ' . $file);
			if ( 'wp-config-arc.txt' === $file ) {
				rename( $workdir . 'wp-config-arc.txt', $workdir . 'wp-config.php' );
DS_Clean_Import::debug(__METHOD__.'(): renamed ' . $workdir . $file);
				return;
			}
			if ( 'dup-wp-config-arc__' === substr($file, 0, 19) && '.txt' === substr( $file, -4 ) ) {
				$guid = substr( $file, 19 );
				$guid = substr( $guid, 0, strlen( $guid ) - 4 );
				rename( $workdir . $file, $workdir . 'wp-config.php' );
DS_Clean_Import::debug(__METHOD__.'(): renamed ' . $workdir . $file);
				$sql_file = $workdir . 'dup-installer' . DIRECTORY_SEPARATOR . 'dup-database__' . $guid . '.sql';
				rename( $sql_file, $workdir . 'database.sql' );
DS_Clean_Import::debug(__METHOD__.'(): renamed ' . $sql_file);
				return;
			}
		}
DS_Clean_Import::debug(__METHOD__.'(): no duplicator type file found');
	}
}
