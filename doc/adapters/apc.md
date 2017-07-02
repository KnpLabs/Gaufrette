---
currentMenu: apc
---

# APC

**Warning: This adapter has been deprecated since v0.4.0 and will be removed in v1.0.0.**

`apc` extension should be enabled in order to use this adapter.

## Example

`Apc` adpater takes only two arguments :
  * the first, mandatory, is a prefix to avoid conflicts between filesystems
  * the second, not mandatory, is the ttl for each file stored

```php
<?php

use Gaufrette\Adapter\Apc as ApcAdapter;
use Gaufrette\Filesystem;

$adapter = new ApcAdapter('/prefix', 600);
$filesystem = new Filesystem($adapter);
```
