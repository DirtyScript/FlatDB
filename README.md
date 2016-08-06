  Dirty Script > FlatDB
==========================

PHP flat file data base class.<br />
based on simple key => value.<br />
key can be a string, a date, a int ...
value can be array, string, int ...

This system works well for small projects that do not require a large database.

need more test and improvements, so use it for fun/test/debug/small project...

```php
/**
 * DSDB - Dirty Script Data Base
 * 
 * a flat file basic data base
 * manage datas by key(id)
 * 
 * PHP version 5.3
 * 
 * @author     RemRem <remrem@dirty-script.com>
 * @copyright  2014-2016
 * @licence    MIT
 * @link       http://dirty-script.com/Data-Base
 * @link       https://github.com/DirtyScript/FlatDB
 */
```

### install & use with composer
Using FlatDB with composer is quite easy. Just add DirtyScript\FlatDB to your projects requirements.


```php
require_once 'vendor/autoload.php';

use DirtyScript\FlatDB;
```


### init a db
db-name must be the full path to your database file
don't need to put an extension
```php
$your_db = new FlatDB( '/var/www/database/db-name' , true );
```
FlatDB will create `db-name.json.gz.php`
make sure of `/var/www/database/` can be readable and writable.

### store a data
```php
$your_db->data_push('you-key','This is your data');
```
return bool. by default data_push() don't overwrite data if the key is already here, 
if you want overwrite the data, just add a 3rd option (true)
```php
$your_db->data_push('key','datas',true);
```
make overwrite the data stored for 'key'

if you dont want to handle the key and let FlatDB handle it
```php
$your_db->data_push(null,'datas');
```

### read a data
```php
$your_data = $your_db->data_get('key');
```
you must provide the 'key'.

### get all keys
```php
$your_keys = $your_db->data_keys();
```
return an array with all the key stored

### remove a data
```php
$your_db->data_remove('key');
```
return bool

### reset the database
```php
$your_db->db_reset();
```
return bool

### reload the database / get all db content
```php
$your_db->db_read();
```
read the database file and return an array with all key => data stored

### get somes infos about the database
```php
$your_db->db_infos();
```
return an array with some infos.

### export
```php
$your_db->db_export( $format );
```
`$format` (string) can be csv, json, xml or serialize.
Need more tests...

### backup
```php
$you_db->db_backup( $backup_name );
```
`$backup_name` (string) the name of the backup
if `$backup_name` is empty, DSDB just add `-backup` in file name before extension.
This function just make a copy of the database file.

### get the last insert
```php
$your_db->data_get_last_line( 3 );
```
get X last line(s)

### search in the data
```php
$your_db->data_search( $test, $limit );
```
try to search for specific data
need some work on this function, I will develop more soon

