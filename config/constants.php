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

//Sets the default directory
if(!defined('BACKUPS'))
	define('BACKUPS', ROOT.DS.'backups');

//Sets the bzip2 executable
if(!defined('BZIP2_BIN'))
	define('BZIP2_BIN', which('bzip2'));

//Sets the gzip executable
if(!defined('GZIP_BIN'))
	define('GZIP_BIN', which('gzip'));

//Sets the mysql executable
if(!defined('MYSQL_BIN'))
	define('MYSQL_BIN', which('mysql'));

//Sets the mysqldump executable
if(!defined('MYSQLDUMP_BIN'))
	define('MYSQLDUMP_BIN', which('mysqldump'));