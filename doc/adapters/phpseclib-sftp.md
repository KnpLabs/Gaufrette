---
currentMenu: phpseclib-sftp
---

# phpseclib adapter

*N.B.* It is recommended to use this adapter over [SFTP](sftp.html).

## Prerequisites

* [phpseclib](https://github.com/phpseclib/phpseclib)

You can install it via:

```bash
composer require phpseclib/phpseclib:^2.0
```

## Configuration

```php

$sftp = new phpseclib\Net\SFTP($host = 'localhost', $port = 22);

//now you need to login manually with the lib
$sftp->login('foo', 'bar');

$adapter = new Gaufrette\Adapter\PhpseclibSftp($sftp, $distantDirectory = null, $createDirectoryIfDoesntExist = false);
$filesystem = new Gaufrette\Filesystem($adapter);
```
