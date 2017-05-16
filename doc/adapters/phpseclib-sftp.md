---
currentMenu: phpseclib-sftp
---

# phpseclib adapter

*N.B.* It is recommended to use this adapter over [SFTP](sftp.html).

## Prerequisites

First, you will need to install the adapter:
```bash
composer require gaufrette/phpseclib-sftp-adapter
```

## Configuration

```php

$sftp = new phpseclib\Net\SFTP($host = 'localhost', $port = 22);

//now you need to login manually with the lib
$sftp->login('foo', 'bar');

$adapter = new Gaufrette\Adapter\PhpseclibSftp($sftp, $distantDirectory = null, $createDirectoryIfDoesntExist = false);
$filesystem = new Gaufrette\Filesystem($adapter);
```
