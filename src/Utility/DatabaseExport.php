<?php
/**
 * This file is part of DatabaseBackup.
 *
 * DatabaseBackup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * DatabaseBackup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with DatabaseBackup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author		Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright	Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license		http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link		http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace DatabaseBackup\Utility;

use Cake\Network\Exception\InternalErrorException;

/**
 * Utility to export the database.
 * 
 * Examples.
 * 
 * This will create a backup file with the `gzip` compression.  
 * By default, the file will be created in the `APP/backup` directory, with a default name.
 * <code>
 * $backup = new DatabaseExport();
 * $backup->compression('gzip');
 * $backup->export();
 * </code>
 * 
 * This will create the backup file `/opt/backups/mybackup.sql.bz2`.  
 * It will use the `bzip2` compression (is automatically detected by the filename).
 * <code>
 * $backup = new DatabaseExport();
 * $backup->filename('/opt/backups/mybackup.sql.bz2');
 * $backup->export();
 * </code>
 */
class DatabaseExport {
	/**
	 * Compression type. 
	 * Use the `compression()` method to set the compression type.
	 * With the `filename()` method, the compression type will be automatically set.
	 * @var string
	 * @see compression()
	 * @see filename()
	 */
	protected $compression;
	
	/**
	 * Database connection.
	 * Use the `connection()` method to set the database connection.
	 * @var array 
	 * @see connection()
	 */
	protected $connection;
	
	/**
	 * Executable command.
	 * @var string 
	 */
	protected $executable;
	
	/**
	 * Filename extension
	 * @var string
	 */
	protected $extension;
	
	/**
	 * Filename where to export the database.
	 * Use the `filename()` method to set the filename where to export the database.
	 * @var string
	 * @see filename()
	 */
	protected $filename;
	
	/**
	 * Sets the compression type.
	 * 
	 * Supported values: `gzip`, `bzip2` and `none` (no compression)
	 * @param string $compression Compression type
	 * @return string Compression type
	 * @uses $compression
	 * @uses $executable
	 * @uses $extension
	 * @throws InternalErrorException
	 */
	public function compression($compression) {
		switch($compression) {
			case 'gzip':
				$this->compression = 'gzip';
				$this->executable = 'mysqldump --defaults-file=%s %s | gzip > %s';
				$this->extension = 'sql.gz';
				break;
			case 'bzip2':
				$this->compression = 'bzip2';
				$this->executable = 'mysqldump --defaults-file=%s %s | bzip2 > %s';
				$this->extension = 'sql.bz2';
				break;
			case 'none':
				$this->compression = 'none';
				$this->executable = 'mysqldump --defaults-file=%s %s > %s';
				$this->extension = 'sql';
				break;
			default:
				throw new InternalErrorException(__d('database_backup', 'Compression type not supported'));
				break;
		}
		
		return $this->compression;
	}
	
	/**
	 * Sets the database connection to use.
	 * The connection must be defined in `APP/config/app.php`.
	 * @param string $connection Connection name
	 * @return array Connection parameters
	 * @use $connection
	 * @throws InternalErrorException
	 */
	public function connection($connection) {		
		$this->connection = \Cake\Datasource\ConnectionManager::config($connection);
		
		if(empty($this->connection))
			throw new InternalErrorException(__d('database_backup', 'Invalid connection'));
		
		return $this->connection;
	}
	
	/**
	 * Sets the filename where to export the database.
	 * 
	 * If the filename is relative, then it's relative to the APP root.
	 * 
	 * Using this method, the compression type will be automatically detected by the filename.
	 * @param string $filename Filename path, absolute or relative (will be relative to the APP root) 
	 * @return string Filename path
	 * @uses $filename
	 * @uses compression()
	 * @throws InternalErrorException
	 */
	public function filename($filename) {
		//If the filename is relative, then it's relative to the APP root
		$filename = \Cake\Filesystem\Folder::isAbsolute(dirname($filename)) ? $filename : ROOT.DS.$filename;
		
		if(!is_writable(dirname($filename)))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` not writeable', dirname($filename)));
		
		if(file_exists($filename))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` already exists', $filename));

		//Checks if the file has an extension
		if(!preg_match('/\.(.+)$/', $filename, $matches))
			throw new InternalErrorException(__d('database_backup', 'Invalid file extension'));

		//Sets the compression type
		switch($matches[1]) {
			case 'sql.gz':
				$this->compression('gzip');
				break;
			case 'sql.bz2':
				$this->compression('bzip2');
				break;
			case 'sql':
				$this->compression('none');
				break;
			default:
				throw new InternalErrorException(__d('database_backup', 'Compression type not supported'));
				break;
		}
		
		return $this->filename = $filename;
	}

	/**
	 * Exports the database
	 * @return string Filename path
	 * @uses $compression
	 * @uses $connection
	 * @uses $extension
	 * @uses $filename
	 * @uses compression()
	 * @uses connection()
	 * @uses filename()
	 * @throws InternalErrorException
	 */
	public function export() {
		//Sets the default database connection
		if(empty($this->connection))
			$this->connection('default');
		
		//Sets the default compression type (no compression)
		if(empty($this->compression))
			$this->compression('none');
		
		//Sets the default filename where to export the database
		if(empty($this->filename))		
			$this->filename(ROOT.DS.'backup'.DS.sprintf('backup_%s_%s.%s', $this->connection['database'], date('YmdHis'), $this->extension));
		
		//For security reasons, it's recommended to specify the password in a configuration file and 
		//not in the command (a user can execute a `ps aux | grep mysqldump` and see the password)
		//So it creates a temporary file to store the configuration options
		$mysqldump = tempnam(sys_get_temp_dir(), 'mysqldump');
		file_put_contents($mysqldump, sprintf("[mysqldump]\nuser=%s\npassword=\"%s\"\nhost=%s", $this->connection['username'], $this->connection['password'], $this->connection['host']));
		
		//Executes
		exec(sprintf($this->executable, $mysqldump, $this->connection['database'], $this->filename));
		
		//Deletes the temporary file
		unlink($mysqldump);
		
		if(!is_readable($this->filename))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` has not been created', $filename));
		
		return $this->filename;
	}
}