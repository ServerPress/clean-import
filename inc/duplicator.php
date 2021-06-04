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

			// scan for dup-installer/original_files_* directory
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' searching for "' . $workdir . 'dup-installer" directory');
			if ( is_dir( $workdir . 'dup-installer' ) ) {
				$scandir = $workdir . 'dup-installer' . DIRECTORY_SEPARATOR . 'original_files_*';
				$files = scandir( $scandir );
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' searching for ' . $scandir . ' finds: ' . var_export($files, TRUE));
				foreach ( $files as $file ) {
					if ( is_dir( $file ) ) {
						$found = FALSE;
						// if it's a directory, look for specific files within it
						$source = $file . DIRECTORY_SEPARATOR . 'source_site_wpconfig';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking "' . $source . '"');
						if ( file_exists( $source ) ) {
							rename( $source, $workdir . 'wp-config.php' );
							$found = TRUE;
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renamed "' . $source . '" to "' . $workdir . 'wp-config.php"');
						}

						$source = $file . DIRECTORY_SEPARATOR . 'source_site_userini';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking "' . $source . '"');
						if ( file_exists( $source ) ) {
							rename( $source, $workdir . 'user.ini' );
							$found = TRUE;
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renamed "' . $source . '" to "' . $workdir . 'user.ini"');
						}

						$source = $file . DIRECTORY_SEPARATOR . 'source_site_htaccess';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking "' . $source . '"');
						if ( file_exists( $source ) ) {
							rename( $source, $workdir . '.htaccess' );
							$found = TRUE;
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renamed "' . $source . '" to "' . $workdir . '.htaccess"');
						}
						if ( $found)
							break;				// if something was found, copied - can exit the loop
					}
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
