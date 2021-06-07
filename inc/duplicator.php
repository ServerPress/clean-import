<?php

class DS_Clean_Import_Duplicator extends DS_Clean_Import_Base
{
	public function pre_import_process( $info )
	{
DS_Clean_Import::debug(__METHOD__.'():' . var_export($info, TRUE));
		$workdir = $info[2];			// get the temp directory archive was exported to

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

			// set up $dup_installer to contain proper directory name
			$dup_installer = $workdir . 'dup-installer';
			if ( ! is_dir( $dup_installer ) )
				$dup_installer = $workdir . 'ds-dup-installer';

			// scan for dup-installer/original_files_* directory
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' searching for "' . $dup_installer . '" directory');
			if ( is_dir( $dup_installer ) ) {
				$scandir = $dup_installer . DIRECTORY_SEPARATOR . 'original_files_*';
				$files = scandir( $scandir );
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' searching for ' . $scandir . ' finds: ' . var_export($files, TRUE));
				if ( FALSE !== $files ) {
					foreach ( $files as $file ) {
						if ( is_dir( $file ) ) {
							$found = FALSE;
							// if it's a directory, look for specific files within it
							$source = $dup_installer . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . 'source_site_wpconfig';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking "' . $source . '"');
							if ( file_exists( $source ) ) {
								rename( $source, $workdir . 'wp-config.php' );
								$found = TRUE;
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renamed "' . $source . '" to "' . $workdir . 'wp-config.php"');
							}

							$source = $dup_installer . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . 'source_site_userini';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking "' . $source . '"');
							if ( file_exists( $source ) ) {
								rename( $source, $workdir . '.user.ini' );
								$found = TRUE;
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renamed "' . $source . '" to "' . $workdir . 'user.ini"');
							}

							$source = $dup_installer . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . 'source_site_htaccess';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking "' . $source . '"');
							if ( file_exists( $source ) ) {
								rename( $source, $workdir . '.htaccess' );
								$found = TRUE;
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renamed "' . $source . '" to "' . $workdir . '.htaccess"');
							}
							if ( $found )
								break;				// if something was found, copied - can exit the loop
						}
					} // foreach
				} // FALSE !== $files

				// look for database.sql file
DS_Clean_Import::debug(__METHOD__.'(): checking for database.sql');
				if ( file_exists( $workdir . 'database.sql' ) ) {
DS_Clean_Import::debug(__METHOD__.'(): database.sql already exists');
				} else {
					$files = scandir( $dup_installer . DIRECTORY_SEPARATOR );
DS_Clean_Import::debug(__METHOD__.'(): found ' . count($files) . ' files');
	
					foreach ( $files as $file ) {
DS_Clean_Import::debug(__METHOD__.'(): checking file: ' . $file);
						if ( 'dup-database__' == substr( $file, 0, 14 ) && '.sql' === substr( $file, -4 ) ) {
							$sql_file = $dup_installer . DIRECTORY_SEPARATOR . $file;
							rename( $sql_file, $workdir . 'database.sql' );
DS_Clean_Import::debug(__METHOD__.'(): renamed ' . $sql_file);
							break;
						}
					}
				}

			} // is_dir( $dup_installer )
		} // file_exists( 'wp-config' )

DS_Clean_Import::debug(__METHOD__.'(): complete');
	}
}
