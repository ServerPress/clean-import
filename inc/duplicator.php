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
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking file: ' . $workdir . $file);
				if ( 'wp-config-arc.txt' === $file ) {
					rename( $workdir . 'wp-config-arc.txt', $workdir . 'wp-config.php' );
DS_Clean_Import::debug(__METHOD__.'(): renamed ' . $workdir . $file);
					break;
				}
				if ( 'dup-wp-config-arc__' === substr( $file, 0, 19 ) && '.txt' === substr( $file, -4 ) ) {
					$guid = substr( $file, 19 );
					$guid = substr( $guid, 0, strlen( $guid ) - 4 );
					rename( $workdir . $file, $workdir . 'wp-config.php' );
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renamed ' . $workdir . $file . ' to ' . $workdir . 'wp-config.php');
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
				$original_files = NULL;
				$scandir = $dup_installer . DIRECTORY_SEPARATOR;
				$files = scandir( $scandir );
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' searching for ' . $scandir . ' finds: ' . var_export($files, TRUE));
				if ( FALSE !== $files ) {
					foreach ( $files as $file ) {
						if ( is_dir( $scandir . $file ) &&
							'original_files_' === substr( $file, 0, 15 ) && strlen( $file ) > 10 ) {
							$original_files = $scandir . $file . DIRECTORY_SEPARATOR;
							break;
						}
					}
				}
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' original_files=' . $original_files);
				if ( NULL !== $original_files ) {
					$files = scandir( $original_files );
					if ( FALSE === $files ) {
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' no files found in "' . $original_files . '"');
					} else {
						foreach ( $files as $file ) {
							if ( '.' === $file || '..' === $file )
								continue;
							if ( is_dir( $original_files . $file ) ) {
								$found = FALSE;
								// if it's a directory, look for specific files within it
								$source = $original_files . $file . DIRECTORY_SEPARATOR . 'source_site_wpconfig';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking "' . $source . '"');
								if ( file_exists( $source ) ) {
									copy( $source, $workdir . 'wp-config.php' );
									$found = TRUE;
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' copied "' . $source . '" to "' . $workdir . 'wp-config.php"');
if ( ! file_exists( $workdir . 'wp-config.php' ) ) DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' copy failed');
								}

								$source = $original_files . $file . DIRECTORY_SEPARATOR . 'source_site_userini';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking "' . $source . '"');
								if ( file_exists( $source ) ) {
									copy( $source, $workdir . '.user.ini' );
									$found = TRUE;
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' copied "' . $source . '" to "' . $workdir . 'user.ini"');
if ( ! file_exists( $workdir . 'user.ini' ) ) DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' copy failed');
								}

								$source = $original_files . $file . DIRECTORY_SEPARATOR . 'source_site_htaccess';
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' checking "' . $source . '"');
								if ( file_exists( $source ) ) {
									copy( $source, $workdir . '.htaccess' );
									$found = TRUE;
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' copied "' . $source . '" to "' . $workdir . '.htaccess"');
if ( ! file_exists( $workdir . '.htaccess' ) ) DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' copy failed');
								}
								if ( $found )
									break;				// if something was found, copied - can exit the loop
							} // is_dir
						} // foreach
					} // FALSE !== $files
				} // NULL !== $original_files

				// look for database.sql file
DS_Clean_Import::debug(__METHOD__.'(): checking for database.sql');
				if ( file_exists( $workdir . 'database.sql' ) ) {
DS_Clean_Import::debug(__METHOD__.'(): database.sql already exists');
				} else {
					$files = scandir( $dup_installer . DIRECTORY_SEPARATOR );
DS_Clean_Import::debug(__METHOD__.'(): found ' . count($files) . ' files');
	
					foreach ( $files as $file ) {
DS_Clean_Import::debug(__METHOD__.'(): checking file: ' . $file);
						if ( 'dup-database__' === substr( $file, 0, 14 ) && '.sql' === substr( $file, -4 ) ) {
							$sql_file = $dup_installer . DIRECTORY_SEPARATOR . $file;
							rename( $sql_file, $workdir . 'database.sql' );
DS_Clean_Import::debug(__METHOD__.'():' . __LINE__ . ' renamed ' . $sql_file . ' to ' . $workdir . 'database.sql');
							break;
						}
					}
				}

			} // is_dir( $dup_installer )
		} // file_exists( 'wp-config' )

DS_Clean_Import::debug(__METHOD__.'(): complete');
	}
}
