---
currentMenu: local
---

# Local & SafeLocal

Those two adapters aims to use local filesystem. The second one will encode in base64 the filename before storing/retrieving.

## Installation

```bash
* composer require gaufrette/local-adapter:dev-master
```

## Example

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local\Local as LocalAdapter;

$adapter = new LocalAdapter('/var/media', true, 0750);
$filesystem = new Filesystem($adapter);
```
