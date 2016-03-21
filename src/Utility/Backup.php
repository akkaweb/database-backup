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

use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\Network\Exception\InternalErrorException;

/**
 * Utility to manage database backups.
 * 
 * Please, refer to the `README` file to know how to use the utility and to see examples.
 */
class Backup {
	/**
	 * Deletes a backup file
	 * @param string $filename Filename
	 * @return string File path
	 * @throws InternalErrorException
	 * @uses path()
	 */
	public static function delete($filename) {
		$path = self::path($filename);
		
		if(!is_writable($path))
			throw new InternalErrorException(__d('database_backup', 'File or directory {0} not writeable', $path));
		
		if(!(new File($path))->delete())
			throw new InternalErrorException(__d('database_backup', 'Impossible to delete the file {0}', $path));
		
		return $path;
	}
	
	/**
	 * Gets a list of database backups
	 * @return array Objects of backups
	 * @throws InternalErrorException
	 * @uses path()
	 */
	public static function index() {
		if(!is_readable(BACKUPS))
			throw new InternalErrorException(__d('database_backup', 'File or directory {0} not readable', rtr(BACKUPS)));
		
		//Gets all files
		$files = array_values((new Folder(BACKUPS))->read()[1]);
		
		//Parses files
		$files = array_filter(array_map(function($file) {
			preg_match('/(\d{14})?\.(sql|sql\.gz|sql\.bz2)$/i', $file, $matches);
			
			if(empty($matches[2]))
				return FALSE;
			
			//If it cannot detect the date from the filename, it tries to find the last modified date of the file
			if(!empty($matches[1]))
				$datetime = preg_replace('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1-$2-$3 $4:$5:$6', $matches[1]);
			else
				$datetime = date('Y-m-d H:i:s', (new File(self::path($file)))->lastChange());
			
			return (object) [
				'filename' => $file,
				'extension' => $matches[2],
				'compression' => get_compression($matches[2]),
				'datetime' => new \Cake\I18n\FrozenTime($datetime)
			];
		}, $files));
		
		//Re-orders, using the datetime value
		usort($files, function($a, $b) {
			return preg_replace('/\D/', NULL, $b->datetime) - preg_replace('/\D/', NULL, $a->datetime);
		});
		
		return empty($files) ? [] : $files;
	}
	
	/**
	 * Rotates backups.
	 * 
	 * You must indicate the number of backups you want to keep. So, it will delete all backups that are older
	 * @param int $keep Number of files that you want to keep
	 * @return array Files that have been deleted
	 * @throws InternalErrorException
	 * @uses delete()
	 * @uses index()
	 */
	public static function rotate($keep) {
		if($keep <= 0 || !ctype_digit($keep))
			throw new InternalErrorException(__d('database_backup', 'Invalid value for the rotation', $keep));
				
		//Gets all files
		$files = self::index();
		
		//Returns, if the number of files to keep is larger than the number of files that are present
		if($keep >= count($files))
			return FALSE;
		
		//The number of files to be deleted is equal to the number of files that are present less the number of files that you want to keep
		$diff = count($files) - $keep;
		
		//Files that will be deleted
		$files = array_map(function($file) {
			return $file->filename;
		}, array_slice($files, -$diff, $diff));
		
		//Deletes
		foreach($files as $file)
			self::delete($file);
		
		return $files;
	}
}