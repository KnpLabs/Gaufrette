---
currentMenu: doctrine-dbal
--------------------------

# Doctrine DBAL

If you aren't already using Doctrine, you should install DBAL through:

```bash
$ composer require doctrine/dbal
```

In order to use the adapter, you will need to prepare the table with the following columns:

| Columns  |
|----------|
| key      |
| content  |
| mtime    |
| checksum |

## Example

`Doctrine` adapter takes three arguments:
  * the first, mandatory, is a prepared DBAL connection (you can read more about it in [the DBAL docs](http://doctrine-orm.readthedocs.org/projects/doctrine-dbal/en/latest/reference/configuration.html))
  * the second, mandatory, is a table name where the files will be stored
  * the third one is optional array of columns, which allows you to override the default column names

```php
<?php

use Gaufrette\Adapter\DoctrineDbal as DbalAdapter;
use Gaufrette\Filesystem;

$connection = DriverManager::getConnection($params);
$adapter = new DbalAdapter($connection, 'files');
$filesystem = new Filesystem($adapter);
```
