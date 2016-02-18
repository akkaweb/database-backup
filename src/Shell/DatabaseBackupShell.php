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
namespace DatabaseBackup\Shell;

use Cake\Console\Shell;
use Cake\Network\Exception\InternalErrorException;
use DatabaseBackup\Utility\BackupManager;
use DatabaseBackup\Utility\DatabaseExport;

/**
 * Shell to manage the database backups.
 */
class DatabaseBackupShell extends Shell {
	/**
	 * Initializes the Shell acts as constructor for subclasses allows configuration of tasks prior to shell execution
	 * @uses Cake\Console\Shell::initialize()
	 */
	public function initialize() {
        parent::initialize();
		
		//Adds the `<success>` tag for `out()` method
		$this->_io->styles('success', ['text' => 'green']);
	}
	
	/**
	 * Creates a database backup
	 * @uses DatabaseBackup\Utility\DatabaseExport::connection()
	 * @uses DatabaseBackup\Utility\DatabaseExport::compression()
	 * @uses DatabaseBackup\Utility\DatabaseExport::directory()
	 * @uses DatabaseBackup\Utility\DatabaseExport::export()
	 * @uses DatabaseBackup\Utility\DatabaseExport::filename()
	 * @uses DatabaseBackup\Utility\DatabaseExport::rotate()
	 */
	public function backup() {
		try {
			$backup = new DatabaseExport();
		
			//Sets the database connection
			if($this->param('connection'))
				$backup->connection($this->param('connection'));

			//Sets the output directory
			if($this->param('directory'))
				$backup->directory($this->param('directory'));
			
			//Sets the output filename or the compression type
			if($this->param('filename'))
				$backup->filename($this->param('filename'));
			elseif($this->param('compression'))
				$backup->compression($this->param('compression'));
			
			//Sets the rotation
			if($this->param('rotate'))
				$backup->rotate($this->param('rotate'));
			
			//Creates the backup file
			$file = $backup->export();
			
			$this->out(sprintf('<success>%s</success>', __d('database_backup', 'The file {0} has been created', $file)));
		}
		catch(InternalErrorException $e) {
			$this->abort($e->getMessage());
		}
	}
	
	/**
	 * Lists database backups
	 * @uses DatabaseBackup\Utility\BackupManager::index()
	 */
	public function index() {
		//Sets the directory
		$dir = $this->param('directory') ? $this->param('directory') : BACKUP;
		
		try {
			$files = BackupManager::index($dir);
					
			if(!empty($files)) {
				$this->out(__d('database_backup', 'Backup files for {0}', sprintf('<info>%s</info>', $dir)));
				$this->out(__d('database_backup', 'Backup files found: {0}', count($files)), 2);
				
				//Re-indexes and filters
				$files = array_map(function($file) {
					return [$file['filename'], $file['compression'], $file['datetime']];
				}, $files);

				//Table headers
				$headers = [
					__d('database_backup', 'Filename'),
					__d('database_backup', 'Compression type'),
					__d('database_backup', 'Datetime')
				];

				$this->helper('table')->output(array_merge([$headers], $files));
			}
		}
		catch(InternalErrorException $e) {
			$this->abort($e->getMessage());
		}
	}
	
	/**
	 * Rotates backups.
	 * 
	 * You must indicate the number of backups you want to keep. So, it will delete all backups that are older
	 * @param int $keep Number of files that you want to keep
	 */
	public function rotate($keep) {
		//Sets the directory
		$dir = $this->param('directory') ? $this->param('directory') : BACKUP;
		
		try {
			$deleted = BackupManager::rotate($keep, $dir);
			
			if($deleted) {
				foreach($deleted as $file)
					$this->verbose(__d('database_backup', 'The file {0} has been deleted', $file));
				
				$this->out(sprintf('<success>%s</success>', __d('database_backup', 'Deleted backup files: {0}', count($deleted))));
			}
			else
				$this->verbose(__d('database_backup', 'No file has been deleted'));
		
		}
		catch(InternalErrorException $e) {
			$this->abort($e->getMessage());
		}
	}
	
	/**
	 * Gets the option parser instance and configures it.
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		
		return $parser->addSubcommands([
			'backup' => [
				'help' => __d('database_backup', 'Creates a database backup'),
				'parser' => ['options' => [
					'connection' => ['help' => __d('database_backup', 'Database connection to use')],
					'compression'	=> [
						'choices' => ['gzip', 'bzip2', 'none'],
						'help' => __d('database_backup', 'Compression type. By default, no compression will be used'),
						'short' => 'c'
					],
					'directory' => [
						'help' => __d('database_backup', 'Alternative directory you want to use'),
						'short' => 'd'
					],
					'filename' => [
						'help' => __d('database_backup', 'Output file where to save the backup. It can be absolute or relative to the APP root. '
								. 'Using this method, the compression type will be automatically detected by the filename'),
						'short' => 'f'
					],
					'rotate' => [
						'help' => __d('database_backup', 'Rotates backups. You must indicate the number of backups you want to keep. '
								. 'So, it will delete all backups that are older'),
						'short' => 'r'
					]
				]]
			],
			'index' => [
				'help' => __d('database_backup', 'Lists database backups'),
				'parser' => ['options' => [
					'directory' => [
						'help' => __d('database_backup', 'Alternative directory you want to use'),
						'short' => 'd'
					]
				]]
			],
			'rotate' => [
				'parser' => [
					'arguments' => [
						'keep' => [
							'help' => __d('database_backup', 'Number of backups you want to keep'),
							'required' => TRUE
						]
					],
					'options' => [
						'directory' => [
							'help' => __d('database_backup', 'Alternative directory you want to use'),
							'short' => 'd'
						]
					]
				]				
			]
		]);
	}
}
