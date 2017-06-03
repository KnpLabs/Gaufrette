---
currentMenu: basic-usage
---

# Basic Usage

Following an example with the local filesystem adapter. To setup other adapters, look up their respective documentation.

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;

// First, you need a filesystem adapter
$adapter = new LocalAdapter('/var/media');
$filesystem = new Filesystem($adapter);

// Then, you can access your filesystem directly
var_dump($filesystem->read('myFile')); // bool(false)
$filesystem->write('myFile', 'Hello world!');

// Or use File objects
$file = $filesystem->get('myFile');
echo sprintf('%s (modified %s): %s', $file->getKey(), date('d/m/Y, H:i:s', $file->getMtime()), $file->getContent());
// Will print something like: "myFile (modified 17/01/2016 18:40:36): Hello world!"

// You can also rename your file like this:
$file->rename('my/new/file');
```
