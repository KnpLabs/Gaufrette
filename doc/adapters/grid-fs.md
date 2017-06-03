---
currentMenu: grid-fs
---

# GridFS

## Prerequisites

In order to use GridFS adapter, you should have accesible MongoDB instance, [MongoDB PHP driver](http://docs.php.net/manual/en/book.mongodb.php) and  
the [`mongodb/mongodb`](https://docs.mongodb.com/php-library/master/) library installed.

First can install the MongoDB extension with:

```bash
pecl install mongodb
```

Then, install the adapter:
```bash
composer require gaufrette/gridfs-adapter
```

## Usage

```php

$client = new \MongoDB\Client('mongodb://localhost:27017');
$db = $client->selectDatabase('dbname');

$adapter = new \Gaufrette\Adapter\GridFS($db->selectGridFSBucket());
```
