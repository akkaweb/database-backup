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
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */

if (!function_exists('getCompression')) {
    /**
     * Gets the compression type from the file extension
     * @param string $extension File extension
     * @return string Compression type
     * @throws InternalErrorException
     */
    function getCompression($extension)
    {
        if (!array_key_exists($extension, $GLOBALS['supported_extensions'])) {
            throw new InternalErrorException(__d(
                'database_backup',
                'The {0} extension is not supported',
                $extension
            ));
        }

        return $GLOBALS['supported_extensions'][$extension];
    }
}

if (!function_exists('getExtension')) {
    /**
     * Gets the file extension from the compression type
     * @param string $compression Compression type
     * @return string File extension
     * @throws InternalErrorException
     */
    function getExtension($compression)
    {
        $value = array_search($compression, $GLOBALS['supported_extensions']);

        if (!$value) {
            throw new InternalErrorException(__d(
                'database_backup',
                'The {0} compression is not supported',
                $compression
            ));
        }

        return $value;
    }
}

if (!function_exists('rtr')) {
    /**
     * Returns the relative path (to the APP root) of an absolute path
     * @param string $path Absolute path
     * @return string Relativa path
     */
    function rtr($path)
    {
        return preg_replace(
            sprintf('/^%s/', preg_quote(ROOT . DS, DS)),
            null,
            $path
        );
    }
}

if (!function_exists('which')) {
    /**
     * Executes the `which` command.
     *
     * It shows the full path of (shell) commands.
     * @param string $command Command
     * @return string Full path of command
     */
    function which($command)
    {
        return exec(sprintf('which %s', $command));
    }
}
