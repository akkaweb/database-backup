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
namespace DatabaseBackup\Shell;

use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Network\Exception\InternalErrorException;
use DatabaseBackup\Utility\Backup;

/**
 * Shell to handle database backups.
 *
 * Please, refer to the `README` file to know how to use the utility and to see examples.
 */
class BackupShell extends Shell
{
    /**
     * Exports a database backup
     * @return void
     * @uses DatabaseBackup\Utility\BackupExport::connection()
     * @uses DatabaseBackup\Utility\BackupExport::compression()
     * @uses DatabaseBackup\Utility\BackupExport::export()
     * @uses DatabaseBackup\Utility\BackupExport::filename()
     * @uses rotate()
     */
    public function export()
    {
        try {
            $backup = new \DatabaseBackup\Utility\BackupExport();

            //Sets the database connection
            if ($this->param('connection')) {
                $backup->connection($this->param('connection'));
            }

            //Sets the output filename or the compression type
            if ($this->param('filename')) {
                $backup->filename($this->param('filename'));
            } elseif ($this->param('compression')) {
                $backup->compression($this->param('compression'));
            }

            //Exports the backup file
            $file = $backup->export();

            $this->success(__d('database_backup', 'The backup {0} has been exported', $file));

            //Rotates backup files.
            if ($this->param('rotate')) {
                $this->rotate($this->param('rotate'));
            }
        } catch (InternalErrorException $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Lists database backups
     * @return void
     * @uses DatabaseBackup\Utility\Backup::index()
     */
    public function index()
    {
        try {
            //Gets alla files
            $files = Backup::index();

            $this->out(__d('database_backup', 'Backup files found: {0}', count($files)));

            if (!empty($files)) {
                //Re-indexes and filters
                $files = array_map(function ($file) {
                    return [$file->filename, $file->compression, $file->datetime];
                }, $files);

                $this->helper('table')->output(array_merge([[
                    __d('database_backup', 'Filename'),
                    __d('database_backup', 'Compression'),
                    __d('database_backup', 'Datetime')
                ]], $files));
            }
        } catch (InternalErrorException $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Imports a database backup
     * @param string $filename Filename
     * @return void
     * @uses DatabaseBackup\Utility\BackupImport::filename()
     * @uses DatabaseBackup\Utility\BackupImport::import()
     */
    public function import($filename)
    {
        //The filename can be relative to the APP root
        if (!Folder::isAbsolute($filename)) {
            $filename = ROOT . DS . $filename;
        }

        try {
            $backup = new \DatabaseBackup\Utility\BackupImport();
            $backup->filename($filename);

            //Imports the backup file
            $file = $backup->import();

            $this->success(__d('database_backup', 'The backup {0} has been imported', $file));
        } catch (InternalErrorException $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Main command. Alias for `index()`
     * @return void
     * @uses index()
     */
    public function main()
    {
        $this->index();
    }

    /**
     * Rotates backups.
     *
     * You must indicate the number of backups you want to keep. So, it will
     *  delete all backups that are older
     * @param int $keep Number of files that you want to keep
     * @return void
     * @uses DatabaseBackup\Utility\Backup::rotate()
     */
    public function rotate($keep)
    {
        try {
            //Gets deleted files
            $deleted = Backup::rotate($keep);

            if ($deleted) {
                foreach ($deleted as $file) {
                    $this->verbose(__d('database_backup', 'The file {0} has been deleted', $file));
                }

                $this->success(__d('database_backup', 'Deleted backup files: {0}', count($deleted)));
            } else {
                $this->verbose(__d('database_backup', 'No file has been deleted'));
            }
        } catch (InternalErrorException $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Gets the option parser instance and configures it.
     * @return ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        return $parser->addSubcommands([
            'export' => [
                'help' => __d('database_backup', 'Exports a database backup'),
                'parser' => ['options' => [
                    'connection' => [
                        'help' => __d('database_backup', 'Database ' .
                            'connection to use')
                    ],
                    'compression' => [
                        'choices' => ['gzip', 'bzip2', 'none'],
                        'help' => __d('database_backup', 'Compression type. ' .
                            'By default, no compression will be used'),
                        'short' => 'c'
                    ],
                    'filename' => [
                        'help' => __d('database_backup', 'Filename to use ' .
                            'to save the backup. Using this method, the ' .
                            'compression type will be automatically ' .
                            'detected by the filename'),
                        'short' => 'f'
                    ],
                    'rotate' => [
                        'help' => __d('database_backup', 'Rotates backups. ' .
                            'You must indicate the number of backups you ' .
                            'want to keep.  So, it will delete all backups ' .
                            'that are older. By default, no backup will be ' .
                            'canceled'),
                        'short' => 'r'
                    ]
                ]]
            ],
            'index' => [
                'help' => __d('database_backup', 'Lists database backups')
            ],
            'import' => [
                'help' => __d('database_backup', 'Imports a database backup'),
                'parser' => ['arguments' => [
                    'filename' => [
                        'help' => __d('database_backup', 'Filename to import'),
                        'required' => true
                    ]
                ]]
            ],
            'rotate' => [
                'help' => __d('database_backup', 'Rotates backups. You ' .
                    'must indicate the number of backups you want to keep. ' .
                    'So, it will delete all backups that are older'),
                'parser' => ['arguments' => [
                    'keep' => [
                        'help' => __d('database_backup', 'Number of ' .
                            'backups you want to keep'),
                        'required' => true
                    ]
                ]]
            ],
        ]);
    }
}
