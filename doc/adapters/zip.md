---
currentMenu: zip
---

# ZIP

First, you will need to install the adapter:
```bash
composer require gaufrette/zip-adapter
```

You need zip extension too:
```bash
sudo apt-get install libzip-dev # On Debian, Ubuntu, ...
sudo pecl install zip
```

**Warning: this adapter is buggy under Windows.**

## Example

```php
<?php

use Gaufrette\Adapter\Zip as ZipAdapter;
use Gaufrette\Filesystem;

$adapter = new ZipAdapter('/path/to/my/zip/file');
$filesystem = new Filesystem($adapter);
```
