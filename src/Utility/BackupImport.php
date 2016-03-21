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

class BackupImport {
	/**
	 * Database connection.
	 * Use the `connection()` method to set the database connection.
	 * @var array 
	 * @see connection()
	 */
	protected $connection;
    
	/**
	 * Executable command.
	 * This property is only for internal use of the class. You don't need to set it manually.
	 * @var string 
	 */
	protected $executable;
    
	/**
	 * Sets the executable type
	 * @param string $compression Compression type
     * @return boolean
	 * @uses $executable
	 * @throws InternalErrorException
	 */
	protected function _executable($compression) {
		switch($compression) {
			case 'gzip':
                $this->executable = 'gzip -dc %s | mysql --defaults-extra-file=%s %s';
				break;
			case 'bzip2':
                $this->executable = 'bzip2 -dc %s | mysql --defaults-extra-file=%s %s';
				break;
			case 'none':
                $this->executable = 'cat %s | mysql --defaults-extra-file=%s %s';
				break;
			default:
				throw new InternalErrorException(__d('database_backup', 'Compression type not supported'));
		}
		
		return TRUE;
	}
    
	/**
	 * Construct
	 * @param string $connection Connection name
	 * @uses connection()
	 */
	public function __construct($connection = NULL) {
		$this->connection(empty($connection) ? 'default' : $connection);
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
	 * Sets the filename where to export the database
	 * @param string $filename Filename path
     * @return string Filename path
     * @throws InternalErrorException
     * @uses _executable()
     * @uses $filename
     */
    public function filename($filename) {
		if(!is_readable($filename))
			throw new InternalErrorException(__d('database_backup', 'File or directory {0} not readable', $filename));
        
		//Checks if the file has an extension
		if(!preg_match('/\.(.+)$/', pathinfo($filename, PATHINFO_BASENAME), $matches))
			throw new InternalErrorException(__d('database_backup', 'Invalid file extension'));
                
		$this->_executable(get_compression($matches[1]));
        
		return $this->filename = $filename;
    }
    
    /**
     * Imports the database
     * @uses $connection
     * @uses $executable
     * @uses $filename
	 * @return string Filename path
     */
    public function import() {
        if(empty($this->filename))
			throw new InternalErrorException(__d('database_backup', 'The filename is missing'));
        
        //For security reasons, it's recommended to specify the password in a configuration file and 
		//not in the command (a user can execute a `ps aux | grep mysqldump` and see the password)
		//So it creates a temporary file to store the configuration options
		$auth = tempnam(sys_get_temp_dir(), 'auth');
		file_put_contents($auth, sprintf("[client]\nuser=%s\npassword=\"%s\"\nhost=%s",$this->connection['username'], $this->connection['password'], $this->connection['host']));
     
		//Executes
		exec(sprintf($this->executable, $this->filename, $auth, $this->connection['database']));
        
		//Deletes the temporary file
		unlink($auth);
        
        return $this->filename;
    }
}