---
currentMenu: local
---

# Local & SafeLocal

Those two adapters aims to use local filesystem. The second one will encode in base64 the filename before storing/retrieving.

First, you will need to install the adapter:
```bash
composer require gaufrette/local-adapter
```

## Example

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;

$adapter = new LocalAdapter('/var/media', true, 0750);
$filesystem = new Filesystem($adapter);
```

## Delete a directory

Directory deletion is a feature only available on the `Local` adapter. It is
not supported by the `FilesysteInterface` which aims to only deal with objects.

Following the above statement, you have to explicitely retrieve the adapter
in order to delete a directory :

```php
$filesystem->getAdapter()->delete($dirKey);
```

Note that you can't delete the root directory of the Local adapter.
