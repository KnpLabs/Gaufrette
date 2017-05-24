# Gaufrette

![climb](http://i.imgur.com/ywnErUd.jpg)

Gaufrette provides a filesystem abstraction layer.

[![Build Status](https://secure.travis-ci.org/KnpLabs/Gaufrette.png)](http://travis-ci.org/KnpLabs/Gaufrette)
[![Join the chat at https://gitter.im/KnpLabs/Gaufrette](https://badges.gitter.im/KnpLabs/Gaufrette.svg)](https://gitter.im/KnpLabs/Gaufrette)
[![Stories in Ready](https://badge.waffle.io/knplabs/gaufrette.png?label=ready&title=Ready)](https://waffle.io/knplabs/gaufrette)

## Why use Gaufrette?

Imagine you have to manage a lot of medias in a PHP project. Lets see how to
take this situation in your advantage using Gaufrette.

The filesystem abstraction layer permits you to develop your application without
the need to know were all those medias will be stored and how.

Another advantage of this is the possibility to update the files location
without any impact on the code apart from the definition of your filesystem.
In example, if your project grows up very fast and if your server reaches its
limits, you can easily move your medias in an Amazon S3 server or any other
solution.

## Documentation

Read the official [Gaufrette documentation](doc/index.md).

## Symfony integration

Symfony integration is available through [KnpLabs/KnpGaufretteBundle](https://github.com/KnpLabs/KnpGaufretteBundle).

## Setup the vendor libraries

As some filesystem adapters use vendor libraries, you should install the vendors:

    $ cd gaufrette
    $ php composer.phar install
    $ sh bin/configure_test_env.sh

It will avoid skip a lot of tests.

## Launch the Test Suite

In the Gaufrette root directory:

To check if classes specification pass:

    $ php bin/phpspec run

To check basic functionality of the adapters (adapters should be configured you will see many skipped tests):

    $ bin/phpunit

Is it green?

## Note

This project does not have any stable release yet but we do not want to break BC now.
