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

/**
 * Utility to handle database backups
 */
class BackupManager {
	/**
	 * Gets a list of database backups
	 * @param string $dir Alternative directory from which to read backups
	 * @return array
	 */
	public static function getList($dir = NULL) {
		//Gets all files
		$files = array_values((new \Cake\Filesystem\Folder(empty($dir) ? BACKUP : $dir))->read()[1]);
		
		//Parses files
		$files = array_filter(array_map(function($file) {
			if(!preg_match('/(\d{14})\.(sql|sql\.gz|sql\.bz2)$/i', $file, $matches))
				return FALSE;
			
			return [
				'filename' => $file,
				'extension' => $matches[2],
				'compression' => get_compression($matches[2]),
				'datetime' => preg_replace('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1-$2-$3 $4:$5:$6', $matches[1])
			];
		}, $files));
		
		//Re-orders, using the datetime value
		usort($files, function($a, $b) {
			return preg_replace('/\D/', NULL, $b['datetime']) - preg_replace('/\D/', NULL, $a['datetime']);
		});
		
		return empty($files) ? [] : $files;
	}
}