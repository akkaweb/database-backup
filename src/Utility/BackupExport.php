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
use DatabaseBackup\Utility\BackupManager;

/**
 * Utility to export the database.
 * 
 * Examples.
 * 
 * This will create a backup file with the `gzip` compression and a default filename.
 * In addition, only 10 backup files will be kept, the oldest will be deleted.
 * <code>
 * $backup = new BackupExport();
 * $backup->compression('gzip');
 * $backup->rotate(10);
 * $backup->export();
 * </code>
 * 
 * This will create the backup file `mybackup.sql.bz2`.
 * It will use the `bzip2` compression (is automatically detected by the filename).
 * <code>
 * $backup = new BackupExport();
 * $backup->filename('mybackup.sql.bz2');
 * $backup->export();
 * </code>
 */
class BackupExport {
	/**
	 * Executable command.
	 * This property is only for internal use of the class. You don't need to set it manually.
	 * @var string 
	 */
	protected static $_executable;
	
	/**
	 * Filename extension.
	 * This property is only for internal use of the class. You don't need to set it manually.
	 * @var string
	 */
	protected static $_extension;
	
	/**
	 * Compression type. 
	 * Use the `compression()` method to set the compression type.
	 * With the `filename()` method, the compression type will be automatically set.
	 * @var string
	 * @see compression()
	 * @see filename()
	 */
	protected static $compression;
	
	/**
	 * Database connection.
	 * Use the `connection()` method to set the database connection.
	 * @var array 
	 * @see connection()
	 */
	protected static $connection;
	
	/**
	 * Filename where to export the database.
	 * Use the `filename()` method to set the filename where to export the database.
	 * @var string
	 * @see filename()
	 */
	protected static $filename;
	
	/**
	 * Rotate. This is the number of backups you want to keep. So, it will delete all backups that are older.
	 * Use the `rotate()` method to set the filename where to export the database.
	 * @var int
	 * @see rotate()
	 */
	protected static $rotate;
	
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
	public static function compression($compression) {
		switch($compression) {
			case 'gzip':
				self::$_executable = 'mysqldump --defaults-file=%s %s | gzip > %s';
				self::$_extension = 'sql.gz';
				self::$compression = 'gzip';
				break;
			case 'bzip2':
				self::$_executable = 'mysqldump --defaults-file=%s %s | bzip2 > %s';
				self::$_extension = 'sql.bz2';
				self::$compression = 'bzip2';
				break;
			case 'none':
				self::$_executable = 'mysqldump --defaults-file=%s %s > %s';
				self::$_extension = 'sql';
				self::$compression = 'none';
				break;
			default:
				throw new InternalErrorException(__d('database_backup', 'Compression type not supported'));
				break;
		}
		
		return self::$compression;
	}
	
	/**
	 * Sets the database connection to use and returns the connection parameters.
	 * The connection must be defined in `APP/config/app.php`.
	 * @param string $connection Connection name
	 * @return array Connection parameters
	 * @use $connection
	 * @throws InternalErrorException
	 */
	public static function connection($connection) {		
		self::$connection = \Cake\Datasource\ConnectionManager::config($connection);
		
		if(empty(self::$connection))
			throw new InternalErrorException(__d('database_backup', 'Invalid connection'));
		
		return self::$connection;
	}
	
	/**
	 * Sets the filename where to export the database.
	 * 
	 * Using this method, the compression type will be automatically detected by the filename.
	 * @param string $filename Filename path
	 * @return string Filename path
	 * @uses DatabaseBackup\Utility\BackupManager::path()
	 * @uses compression()
	 * @uses $filename
	 * @throws InternalErrorException
	 */
	public static function filename($filename) {
		$filename = BackupManager::path($filename);
		
		if(!is_writable(dirname($filename)))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` not writeable', dirname($filename)));
		
		if(file_exists($filename))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` already exists', $filename));

		//Checks if the file has an extension
		if(!preg_match('/\.(.+)$/', $filename, $matches))
			throw new InternalErrorException(__d('database_backup', 'Invalid file extension'));

		self::compression(get_compression($matches[1]));
		
		return self::$filename = $filename;
	}

	/**
	 * Exports the database
	 * @return string Filename path
	 * @uses DatabaseBackup\Utility\BackupManager::rotate()
	 * @uses compression()
	 * @uses connection()
	 * @uses filename()
	 * @uses $_executable
	 * @uses $_extension
	 * @uses $compression
	 * @uses $connection
	 * @uses $directory
	 * @uses $filename
	 * @uses $rotate
	 * @throws InternalErrorException
	 */
	public static function export() {
		//Sets the default database connection
		//This is not done in the constructor, because the "default" connection might not exist
		if(empty(self::$connection))
			self::connection('default');
		
		//Sets default compression type
		if(empty(self::$compression))
			self::compression('none');
				
		//Sets the default filename where to export the database
		//This is not done in the constructor, because you can set and alternative output directory
		if(empty(self::$filename))		
			self::filename(sprintf('backup_%s_%s.%s', self::$connection['database'], date('YmdHis'), self::$_extension));
		
		//For security reasons, it's recommended to specify the password in a configuration file and 
		//not in the command (a user can execute a `ps aux | grep mysqldump` and see the password)
		//So it creates a temporary file to store the configuration options
		$mysqldump = tempnam(sys_get_temp_dir(), 'mysqldump');
		file_put_contents($mysqldump, sprintf("[mysqldump]\nuser=%s\npassword=\"%s\"\nhost=%s", self::$connection['username'], self::$connection['password'], self::$connection['host']));
		
		//Executes
		exec(sprintf(self::$_executable, $mysqldump, self::$connection['database'], self::$filename));
		
		//Deletes the temporary file
		unlink($mysqldump);
		
		debug(self::$filename); exit;
		
		if(!is_readable(self::$filename))
			throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` has not been created', self::$filename));
		
		@chmod(self::$filename, 0766);
		
		//Rotates backups
		if(!empty(self::$rotate))
			BackupManager::rotate(self::$rotate);
		
		return self::$filename;
	}
	
	/**
	 * Sets the number of backups you want to keep. So, it will delete all backups that are older
	 * @param int $rotate Number of backups you want to keep
	 * @return int Number of backups you want to keep
	 * @uses $rotate
	 */
	public static function rotate($rotate) {
		return self::$rotate = $rotate;
	}
}