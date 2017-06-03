---
currentMenu: zip
---

# ZIP

First, you will need to install the adapter:
```bash
composer require gaufrette/zip-adapter
```

## Example

```php
<?php

use Gaufrette\Adapter\Zip as ZipAdapter;
use Gaufrette\Filesystem;

$adapter = new ZipAdapter('/path/to/my/zip/file');
$filesystem = new Filesystem($adapter);
```
