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

use Cake\Filesystem\Folder;
use Cake\Network\Exception\InternalErrorException;
use DatabaseBackup\Utility\BackupManager;

/**
 * Utility to export the database.
 * 
 * Examples.
 * 
 * This will create a backup file with the `gzip` compression.  
 * The file will be created in the default directory, with a default name.
 * In addition, only 10 backup files will be kept, the oldest will be deleted.
 * <code>
 * $backup = new DatabaseExport();
 * $backup->compression('gzip');
 * $backup->rotate(10);
 * $backup->export();
 * </code>
 * 
 * This will create the backup file `mybackup.sql.bz2`. in the directory `/opt/backups`.
 * It will use the `bzip2` compression (is automatically detected by the filename).
 * <code>
 * $backup = new DatabaseExport();
 * $backup->directory('/opt/backups');
 * $backup->filename('mybackup.sql.bz2');
 * $backup->export();
 * </code>
 */
class DatabaseExport {
	/**
	 * Executable command.
	 * This property is only for internal use of the class. You don't need to set it manually.
	 * @var string 
	 */
	protected $_executable;
	
	/**
	 * Filename extension.
	 * This property is only for internal use of the class. You don't need to set it manually.
	 * @var string
	 */
	protected $_extension;
	
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
	 * Output directory.
	 * Use the `directory()` method to set the database connection.
	 * @var string
	 * @see directory()
	 */
	protected $directory;
	
	/**
	 * Filename where to export the database.
	 * Use the `filename()` method to set the filename where to export the database.
	 * @var string
	 * @see filename()
	 */
	protected $filename;
	
	/**
	 * Rotate. This is the number of backups you want to keep. So, it will delete all backups that are older.
	 * Use the `rotate()` method to set the filename where to export the database.
	 * @var int
	 * @see rotate()
	 */
	protected $rotate;

	/**
	 * Construct. It sets some default properties
	 * @uses compression()
	 * @uses directory()
	 */
	public function __construct() {
		//Sets default compression type and output directory
		$this->compression('none');
		$this->directory(BACKUP);
	}
	
	/**
	 * Sets the compression type.
	 * 
	 * Supported values: `gzip`, `bzip2` and `none` (no compression)
	 * @param string $compression Compression type
	 * @return string Compression type
	 * @uses $_executable
	 * @uses $_extension
	 * @uses $compression
	 * @throws InternalErrorException
	 */
	public function compression($compression) {
		switch($compression) {
			case 'gzip':
				$this->_executable = 'mysqldump --defaults-file=%s %s | gzip > %s';
				$this->_extension = 'sql.gz';
				$this->compression = 'gzip';
				break;
			case 'bzip2':
				$this->_executable = 'mysqldump --defaults-file=%s %s | bzip2 > %s';
				$this->_extension = 'sql.bz2';
				$this->compression = 'bzip2';
				break;
			case 'none':
				$this->_executable = 'mysqldump --defaults-file=%s %s > %s';
				$this->_extension = 'sql';
				$this->compression = 'none';
				break;
			default:
				throw new InternalErrorException(__d('database_backup', 'Compression type not supported'));
				break;
		}
		
		return $this->compression;
	}
	
	/**
	 * Sets the database connection to use and returns the connection parameters.
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
	 * Sets the output directory.
	 * 
	 * If the directory is relative, then will be relative to the APP root.
	 * @param string $directory Directory path
	 * @return string Directory path
	 * @throws InternalErrorException
	 * @uses $directory
	 */
	public function directory($directory) {
		//If the directory is relative, then will be relative to the APP root
		$directory = Folder::isAbsolute($directory) ? $directory : ROOT.DS.$directory;
		
		if(!is_writable($directory))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` not writeable', $directory));
		
		return $this->directory = $directory;
	}
	
	/**
	 * Sets the filename where to export the database.
	 * 
	 * Using this method, the compression type will be automatically detected by the filename.
	 * @param string $filename Filename path
	 * @return string Filename path
	 * @uses compression()
	 * @uses $directory
	 * @uses $filename
	 * @throws InternalErrorException
	 */
	public function filename($filename) {		
		$filename = (Folder::isSlashTerm($this->directory) ? $this->directory : $this->directory.DS).$filename;
		
		if(!is_writable(dirname($filename)))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` not writeable', dirname($filename)));
		
		if(file_exists($filename))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` already exists', $filename));

		//Checks if the file has an extension
		if(!preg_match('/\.(.+)$/', $filename, $matches))
			throw new InternalErrorException(__d('database_backup', 'Invalid file extension'));

		$this->compression(get_compression($matches[1]));
		
		return $this->filename = $filename;
	}

	/**
	 * Exports the database
	 * @return string Filename path
	 * @uses DatabaseBackup\Utility\BackupManager::rotate()
	 * @uses connection()
	 * @uses filename()
	 * @uses $_executable
	 * @uses $_extension
	 * @uses $connection
	 * @uses $directory
	 * @uses $filename
	 * @uses $rotate
	 * @throws InternalErrorException
	 */
	public function export() {
		//Sets the default database connection
		//This is not done in the constructor, because the "default" connection might not exist
		if(empty($this->connection))
			$this->connection('default');
				
		//Sets the default filename where to export the database
		//This is not done in the constructor, because you can set and alternative output directory
		if(empty($this->filename))		
			$this->filename(sprintf('backup_%s_%s.%s', $this->connection['database'], date('YmdHis'), $this->_extension));
		
		//For security reasons, it's recommended to specify the password in a configuration file and 
		//not in the command (a user can execute a `ps aux | grep mysqldump` and see the password)
		//So it creates a temporary file to store the configuration options
		$mysqldump = tempnam(sys_get_temp_dir(), 'mysqldump');
		file_put_contents($mysqldump, sprintf("[mysqldump]\nuser=%s\npassword=\"%s\"\nhost=%s", $this->connection['username'], $this->connection['password'], $this->connection['host']));
		
		//Executes
		exec(sprintf($this->_executable, $mysqldump, $this->connection['database'], $this->filename));
		
		//Deletes the temporary file
		unlink($mysqldump);
		
		if(!is_readable($this->filename))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` has not been created', $filename));
		
		@chmod($this->filename, 0755);
		
		//Rotates backups
		if(!empty($this->rotate))
			BackupManager::rotate($this->rotate, $this->directory);
		
		return $this->filename;
	}
	
	/**
	 * Sets the number of backups you want to keep. So, it will delete all backups that are older
	 * @param int $rotate Number of backups you want to keep
	 * @return int Number of backups you want to keep
	 * @uses $rotate
	 */
	public function rotate($rotate) {
		return $this->rotate = $rotate;
	}
}