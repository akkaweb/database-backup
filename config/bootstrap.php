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

use Cake\Network\Exception\InternalErrorException;

//Sets the default directory
if(!defined('BACKUPS'))
	define('BACKUPS', ROOT.DS.'backups');

if(!function_exists('get_compression')) {
	/**
	 * Get the compression type from the file extension
	 * @param string $extension Extension
	 * @return string Compression type
	 * @throws InternalErrorException
	 */
	function get_compression($extension) {
        $extensions = ['sql.gz' => 'gzip', 'sql.bz2' => 'bzip2', 'sql' => 'none'];
        
        if(!array_key_exists($extension, $extensions))
			throw new InternalErrorException(__d('database_backup', 'The {0} extension is not supported', $extension));
        
        return $extensions[$extension];
	}
}
