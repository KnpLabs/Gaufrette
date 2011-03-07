Gaufrette
=========

Gaufrette is a PHP5 library that provides a filesystem abstraction layer.

This project is under intensive development. Everything can change at any time!

Why use Gaufrette?
------------------

Imagine you have to manage a lot of medias in a PHP project. Lets see how to
take this situation in your advantage using Gaufrette.

The filesystem abstraction layer permits you to develop your application without
the need to know were all those medias will be stored and how.

Another advantage of this is the possibility to update the files location
without any impact on the code apart from the definition of your filesystem.
In exemple, if your project grows up very fast and if your server reaches its
limits, you can easily move your medias in an Amazon S3 server or any other
solution.

Try it!
-------

### Setup your filesystem

    <?php

    use Gaufrette\Filesystem\Filesystem;
    use Gaufrette\Filesystem\Adapter\Local as LocalAdapter;
    
    $adapter = new LocalAdapter('/var/media');
    $filesystem = new Filesystem($adapter)

### Use the filesystem

    <?php
    
    // ... setup your filesystem

    $content = $filesystem->read('myFile');
    
    $content = 'Hello I am the new content';

    $filesystem->write('myFile', $content);

### Use file objects

Gaufrette also provide a File class that is a representation of files in a filesystem

    <?php

    $file = new File('newFile', $filesystem);
    $file->setContent('Hello World');

    echo $file->getContent(); // Hello World

### Cache a slow filesystem

If you have to deal with a slow filesystem, it is out of quetion to use it directly.
So, you need a cache! Happily, Gaufrette offers a cache system ready for use.
It consist of an adapter itself composed of two adapters:

    * The *source* adapter that should be cached
    * The *cache* adapter that is used to cache

Here is an exemple of how to cache an ftp filesystem:

    <?php

    use Gaufrette\Filesystem\Filesystem;
    use Gaufrette\Filesystem\Adapter\Ftp as FtpAdapter;
    use Gaufrette\Filesystem\Adapter\Local as LocalAdapter;

    // create an ftp adapter instance as $ftp and a local one as $local

    $cachedFtp = new CacheAdapter($ftp, $local, 10);

    $filesystem = new Filestystem($cachedFtp);

The third parameter of the cache adapter is the time to live of the cache.

Using Gaufrette in a Symfony2 project
-------------------------------------

As you can see, Gaufrette provides an elegant way to declare your filesystems.
If you want to use them in a Symfony2 project, you can simply add them as
services of your dependency injection container.
