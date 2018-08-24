<?php

class DS_Clean_Import_AriaEngine extends DS_Clean_Import_Base
{
	public function pre_import_process( $info )
	{
DS_Clean_Import::debug(__METHOD__.'():' . var_export($info, TRUE));
		$workdir = $info[2];

		if ( 'Darwin' === PHP_OS )
			$cmd = 'sed ';
		else
			$cmd = 'C:\\xampplite\\ds-plugins\\ds-cli\\platform\\win32\\cygwin\\bin\\sed.exe ';
		$params = '"s/ENGINE\=Aria/ENGINE\=InnoDB/g" ';

DS_Clean_Import::debug(__METHOD__.'():' . $cmd . $params);

		// scan for .sql files to be updated
		$files = scandir( $workdir );
		$count = 0;
DS_Clean_Import::debug(__METHOD__.'(): found ' . count($files) . ' files');
		foreach ($files as $file) {
DS_Clean_Import::debug(__METHOD__.'(): checking file: ' . $file);
			if ( '.sql' === substr( $file, -4 ) ) {
				$workfile = $workdir . $file;
copy($workfile, 'c:\\temp\\cidebug\\' . $file . '-ab');
				$exec = $cmd . $params . ' ' . $workfile . ' >' . $workfile . '-out';
DS_Clean_Import::debug(__METHOD__.'(): exec: ' . $exec);
				$res = shell_exec( $exec );
				rename( $workfile . '-out', $workfile );
DS_Clean_Import::debug(__METHOD__.'(): res: ' . $res);
copy($workfile, 'c:\\temp\\cidebug\\' . $file . '-aa');

				++$count;
			}
		}
DS_Clean_Import::debug(__METHOD__.'(): modified ' . $count . ' files');
	}
}
