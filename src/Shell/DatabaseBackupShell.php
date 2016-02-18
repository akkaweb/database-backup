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
	 * @uses DatabaseBackup\Utility\DatabaseExport::export()
	 * @uses DatabaseBackup\Utility\DatabaseExport::filename()
	 */
	public function backup() {
		try {
			$backup = new \DatabaseBackup\Utility\DatabaseExport();
		
			//Sets the connection parameter
			if($this->param('connection'))
				$backup->connection($this->param('connection'));

			//Sets the output file or the compression parameters
			if($this->param('output'))
				$backup->filename($this->param('output'));
			elseif($this->param('compression'))
				$backup->compression($this->param('compression'));
			
			//Creates the backup file
			$this->out(sprintf('<success>%s</success>', __d('database_backup', 'The file {0} has been created', $backup->export())));
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
	 * @param int $limit Number of files that you want to keep
	 */
	public function rotate($limit) {
		//Sets the directory
		$dir = $this->param('directory') ? $this->param('directory') : BACKUP;
		
		try {
			$deleted = BackupManager::rotate($limit, $dir);
			
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
					'output' => [
						'help' => __d('database_backup', 'Output file where to save the backup. It can be absolute or relative to the APP root. '
								. 'Using this method, the compression type will be automatically detected by the filename'),
						'short' => 'o'
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
						'limit' => [
							'help' => __d('database_backup', 'Limit for the backup rotation. It indicates the number of backups you want to keep'),
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
