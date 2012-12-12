Gaufrette
=========

Gaufrette is a PHP5 library that provides a filesystem abstraction layer.

This project is under intensive development but we do not want to break BC.

[![Build Status](https://secure.travis-ci.org/KnpLabs/Gaufrette.png)](http://travis-ci.org/KnpLabs/Gaufrette)

Why use Gaufrette?
------------------

Imagine you have to manage a lot of medias in a PHP project. Lets see how to
take this situation in your advantage using Gaufrette.

The filesystem abstraction layer permits you to develop your application without
the need to know were all those medias will be stored and how.

Another advantage of this is the possibility to update the files location
without any impact on the code apart from the definition of your filesystem.
In example, if your project grows up very fast and if your server reaches its
limits, you can easily move your medias in an Amazon S3 server or any other
solution.

Try it!
-------

### Setup your filesystem

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;

$adapter = new LocalAdapter('/var/media');
$filesystem = new Filesystem($adapter)
```

### Use the filesystem

```php
<?php

// ... setup your filesystem

$content = $filesystem->read('myFile');

$content = 'Hello I am the new content';

$filesystem->write('myFile', $content);
```

### Use file objects

Gaufrette also provide a File class that is a representation of files in a filesystem

```php
<?php

$file = new File('newFile', $filesystem);
$file->setContent('Hello World');

echo $file->getContent(); // Hello World
```

### Cache a slow filesystem

If you have to deal with a slow filesystem, it is out of question to use it directly.
So, you need a cache! Happily, Gaufrette offers a cache system ready for use.
It consist of an adapter itself composed of two adapters:

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

Using Amazon S3
---------------
You will need to specify a CA certificate to be able to talk to Amazon servers
in https. You can use the one which is shipped with the SDK by defining before
creating the ``\AmazonS3`` object:

```php
define("AWS_CERTIFICATE_AUTHORITY", true);
```

Using Gaufrette in a Symfony2 project
-------------------------------------

As you can see, Gaufrette provides an elegant way to declare your filesystems.

In your Symfony2 project, add to ``deps``:

```ini
[gaufrette]
    git=https://github.com/KnpLabs/Gaufrette.git

# if you want to use Amazon S3
[aws-sdk]
    git=https://github.com/amazonwebservices/aws-sdk-for-php
```

and to ``app/autoload.php``, at the end:

```php
// AWS SDK needs a special autoloader
require_once __DIR__.'/../vendor/aws-sdk/sdk.class.php';
```

And then, you can simply add them as services of your dependency injection container.
As an example, here is services declaration to use Amazon S3:

```xml
<service id="acme.s3" class="AmazonS3">
    <argument type="collection">
        <argument key="key">%acme.aws_key%</argument>
        <argument key="secret">%acme.aws_secret_key%</argument>
    </argument>
</service>

<service id="acme.s3.adapter" class="Gaufrette\Adapter\AmazonS3">
    <argument type="service" id="acme.s3"></argument>
    <argument>%acme.s3.bucket_name%</argument>
</service>

<service id="acme.fs" class="Gaufrette\Filesystem">
    <argument type="service" id="acme.s3.adapter"></argument>
</service>
```

Don't forget to set the constant to tell the AWS SDK to use its CA cert (somewhere
that will be executed before creating the ``\AmazonS3`` object):
```php
define("AWS_CERTIFICATE_AUTHORITY", true);
$fs = $container->get('acme.fs');
// use $fs
```

Streaming Files
---------------

Sometimes, you don't have the choice, you must get a streamable file URL (i.e
to transform an image). Let's take a look at the following example:

```php
$adapter = new InMemoryAdapter(array('hello.txt' => 'Hello World!'));
$filesystem = new Filesystem($adapter);

$map = StreamWrapper::getFilesystemMap();
$map->set('foo', $filesystem);

StreamWrapper::register();

echo file_get_contents('gaufrette://foo/hello.txt'); // Says "Hello World!"
```

Running the Tests
-----------------

The tests use phpspec2 and PHPUnit.

### Setup the vendor libraries

As some filesystem adapters use vendor libraries, you should install the vendors:

    $ cd gaufrette
    $ php composer.phar install --dev
    $ sh bin/configure_test_env.sh

It will avoid skip a lot of tests.

### Launch the Test Suite

In the Gaufrette root directory:

To check if classes specification pass:
    $ php bin/phpspec run

To check basic functionality of the adapters (adapters should be configured you will see many skipped tests):
    $ phpunit

Is it green?
