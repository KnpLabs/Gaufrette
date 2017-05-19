---
currentMenu: in-memory
---

# InMemory

This adapter is useful in test environments where you don't want to depend on external filesystems.

First, you will need to install the adapter:
```bash
composer require gaufrette/in-memory-adapter
```

## Example

`InMemory` adapter only takes an array of available files as argument.

```
<?php

use Gaufrette\Adapter\InMemory;
use Gaufrette\Filesystem;

$adapter = new InMemory(array('my/file' => 'its content'));
$filesystem = new Filesystem($adapter);
```
