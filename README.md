# DatabaseBackup
*DatabaseBackup* is a CakePHP plugin to export, import and manage database backups.

## Installation
You can install the plugin via composer:

    $ composer require --prefer-dist mirko-pagliai/database-backup
    
Then, edit `APP/config/bootstrap.php` to load the plugin:

    Plugin::load('DatabaseBackup', ['bootstrap' => TRUE]);
    
By default the plugin uses the `APP/backups` directory to save the backups files.  
So you have to create the directory and make it writable:

    $ mkdir backups/ && chmod 775 backups/

You can change this directory by defining the `BACKUPS` constant until the plugin is loaded. For example:

    define('BACKUPS', 'alternative'.DS.'directory'.DS.'for'.DS.'backups);
    Plugin::load('DatabaseBackup', ['bootstrap' => TRUE]);

## Usage
*DatabaseBackup* provides three classes:
* `Backup` allows you to make various operations with database backups;
* `BackupExport` allows you to export database backups;
* `BackupImport` allows you to import database backups.

Also it provides the `BackupShell`, which allows you to perform various operations from shell.

### Export backups
You can export backups with the `BackupExport` utility.  
The class constructor accepts the connection name that you want to use.

The utility provides these public methods:
* `compression()` sets the compression type. The supported values are `gzip`, `bzip2` and `none`. By default, no compression will be used;
* `connection()` sets the database connection. The connection must be defined in `APP/config/app.php`. By default, the `default` connection will be used;
* `filename()` sets the filename where to export the database. Using this method, the compression type will be automatically detected by the filename. This method accepts some patterns (`{$DATABASE}`, `{$DATETIME}`, `{$HOSTNAME}`, `{$TIMESTAMP}`);
* `rotate()` sets the number of backups you want to keep. So, it will delete all backups that are older.

Finally, the `export()` method exports the database.

Please, refer to the [wiki](https://github.com/mirko-pagliai/database-backup/wiki/Examples) to see examples.

### Import backups
You can import backups with the `BackupImport` utility.  
The class constructor accepts the connection name that you want to use.

The utility provides these public methods:
* `connection()` sets the database connection. The connection must be defined in `APP/config/app.php`. By default, the `default` connection will be used;
* `filename()` sets the filename to use to import the database.

Finally, the `import()` method imports the database.

Please, refer to the [wiki](https://github.com/mirko-pagliai/database-backup/wiki/Examples) to see examples.

### Manage backups with shell
*DatabaseBackup*  provides the `BackupShell`, which allows you to perform various operations from shell.  
Simple, to see all the available commands:

    $ bin/cake backup -h
    
To see all the available options for a command:

    $ bin/cake backup export -h
    
Examples:

    $ bin/cake backup export -c gzip -r 10

This will export a backup file with the `gzip` compression and a default filename. In addition, only 10 backup files will be kept, the oldest will be deleted.

	$ bin/cake backup import backups/my_backup.sql

This will import the backup file `backups/my_backup.sql`.

### Export backups as cron jobs
You can schedule backups by running the plugin shell as cron job. Please refer to the [CakePHP cookbook](http://book.cakephp.org/3.0/en/console-and-shells/cron-jobs.html).

Example.  
The backup runs every day from Monday to Friday, at 3 am. The backup will be compressed with gzip and only the last 10 backups will be kept:

    0 3 * * 1-5 cd /var/www/mysite && bin/cake backup export -c gzip -r 10 # Backup for mysite

## Versioning
For transparency and insight into our release cycle and to maintain backward compatibility, 
MeTools will be maintained under the [Semantic Versioning guidelines](http://semver.org).
