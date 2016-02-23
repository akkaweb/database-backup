# DatabaseBackup
DatabaseBackup is a CakePHP plugin to handle backups.

You can install the plugin via composer:

    $ composer require --prefer-dist mirko-pagliai/database-backup
    
Then, edit `APP/config/bootstrap.php` to load the plugin:

    Plugin::load('DatabaseBackup', ['bootstrap' => TRUE]);
    
By default the plugin uses the `APP/backup` directory to save the backup files.  
So you have to create the directory and make it writable:

    $ mkdir backup && chmod 775 backup

You can change this directory by defining the constant until the plugin is loaded. For example:

    define('BACKUP', 'alternative'.DS.'directory'.DS.'for'.DS.'backups);
    Plugin::load('DatabaseBackup', ['bootstrap' => TRUE]);

## Versioning
For transparency and insight into our release cycle and to maintain backward compatibility, 
MeTools will be maintained under the [Semantic Versioning guidelines](http://semver.org).
