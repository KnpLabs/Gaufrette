---
currentMenu: caching
---

# Caching

If you have to deal with a slow filesystem, it is out of question to use it directly.
So, you need a cache! Happily, Gaufrette offers a cache system ready for use.
It consists of an adapter decorator itself composed of two adapters:

* The *source* adapter that should be cached
* The *cache* adapter that is used to cache

Here is an example of how to cache an ftp filesystem:

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Ftp as FtpAdapter;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Adapter\Cache as CacheAdapter;

// Locale Cache-Directory (e.g. '%kernel.root_dir%/cache/%kernel.environment%/filesystem') with create = true
$local = new LocalAdapter($cacheDirectory, true);
// FTP Adapter with a defined root-path
$ftp = new FtpAdapter($path, $host, $username, $password, $port);

// Cached Adapter with 3600 seconds time to live
$cachedFtp = new CacheAdapter($ftp, $local, 3600);

$filesystem = new Filesystem($cachedFtp);
```

The third parameter of the cache adapter is the time to live of the cache.
