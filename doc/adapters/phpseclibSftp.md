Phpseclib SFTP
==============

Example
-------

The first argument should be an instance of `\Net_SFTP`. Please refer to 
[`phpseclib/phpseclib`](https://github.com/phpseclib/phpseclib) documentation to know how to build it.

The second argument is the base directory you want to use.

The third one indicates whether you want to automatically create directories if they does not exists 
(i.e. when you create a file in a directory that does not exist yet).

```php
<?php

use Gaufrette\Adapter\Sftp as SftpAdapter;
use Gaufrette\Filesystem;

$adapter = new SftpAdapter($sftpClient, '/media', true);
$filesystem = new Filesystem($adapter);
```
