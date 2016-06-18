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

$GLOBALS['supported_extensions'] = [
    'sql.gz' => 'gzip',
    'sql.bz2' => 'bzip2',
    'sql' => 'none',
];

require_once 'global_functions.php';
require_once 'constants.php';

if(!MYSQL_BIN) {
    throw new InternalErrorException(sprintf('The executable file for %s was not found', 'mysql'));
}

if(!MYSQLDUMP_BIN) {
    throw new InternalErrorException(sprintf('The executable file for %s was not found', 'mysqldump'));
}

if(!is_writable(BACKUPS)) {
    throw new InternalErrorException(sprintf('File or directory %s not writeable', BACKUPS));
}