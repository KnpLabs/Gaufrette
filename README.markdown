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

Using Gaufrette in a Symfony2 project
-------------------------------------

As you can see, Gaufrette provides an elegant way to declare your filesystems.
If you want to use them in a Symfony2 project, you can simply add them as
services of your dependency injection container.
