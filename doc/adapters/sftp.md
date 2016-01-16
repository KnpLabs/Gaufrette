SFTP
====

This adapter is based on the `ssh2` extension. If you don't have this extension available and you can't install it,
the [`PhpseclibSftp`](phpseclibSftp.md) adapter is based on a full-php ssh client.

Example
-------

The first argument should be an instance of `\Ssh\Client`. Please refer to 
[`herzult/php-ssh`](https://github.com/Herzult/php-ssh) documentation to know how to build it.

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
