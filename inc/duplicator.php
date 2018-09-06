<?php

class DS_Clean_Import_Duplicator extends DS_Clean_Import_Base
{
	public function pre_import_process( $info )
	{
DS_Clean_Import::debug(__METHOD__.'():' . var_export($info, TRUE));
		$workdir = $info[2];

		$files = NULL;

DS_Clean_Import::debug(__METHOD__.'(): checking wp-config.php');
		if ( file_exists( $workdir . 'wp-config.php' ) ) {
DS_Clean_Import::debug(__METHOD__.'(): wp-config.php already exists');
		} else {
DS_Clean_Import::debug(__METHOD__.'(): wp-config.php not present');

			// scan for dup-wp-config-arc__*.txt files
			$files = scandir( $workdir );
DS_Clean_Import::debug(__METHOD__.'(): found ' . count($files) . ' files');
			foreach ( $files as $file ) {
DS_Clean_Import::debug(__METHOD__.'(): checking file: ' . $file);
				if ( 'wp-config-arc.txt' === $file ) {
					rename( $workdir . 'wp-config-arc.txt', $workdir . 'wp-config.php' );
DS_Clean_Import::debug(__METHOD__.'(): renamed ' . $workdir . $file);
					break;
				}
				if ( 'dup-wp-config-arc__' === substr( $file, 0, 19 ) && '.txt' === substr( $file, -4 ) ) {
					$guid = substr( $file, 19 );
					$guid = substr( $guid, 0, strlen( $guid ) - 4 );
					rename( $workdir . $file, $workdir . 'wp-config.php' );
DS_Clean_Import::debug(__METHOD__.'(): renamed ' . $workdir . $file);
					break;
				}
			}
		}

DS_Clean_Import::debug(__METHOD__.'(): checking database.sql');
		if ( file_exists( $workdir . 'database.sql' ) ) {
DS_Clean_Import::debug(__METHOD__.'(): database.sql already exists');
		} else {
			$files = scandir( $workdir . 'dup-installer' . DIRECTORY_SEPARATOR );
DS_Clean_Import::debug(__METHOD__.'(): found ' . count($files) . ' files');

			foreach ( $files as $file ) {
DS_Clean_Import::debug(__METHOD__.'(): checking file: ' . $file);
				if ( 'dup-database__' == substr( $file, 0, 14 ) && '.sql' === substr( $file, -4 ) ) {
					$sql_file = $workdir . 'dup-installer' . DIRECTORY_SEPARATOR . $file;
					rename( $sql_file, $workdir . 'database.sql' );
DS_Clean_Import::debug(__METHOD__.'(): renamed ' . $sql_file);
					break;
				}
			}
		}

DS_Clean_Import::debug(__METHOD__.'(): complete');
	}
}
