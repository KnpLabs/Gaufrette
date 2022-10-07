Gaufrette
=========

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://stand-with-ukraine.pp.ua)


Gaufrette provides a filesystem abstraction layer.

[![Build Status](https://github.com/KnpLabs/Gaufrette/actions/workflows/ci.yml/badge.svg)](https://github.com/KnpLabs/Gaufrette/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/KnpLabs/Gaufrette.svg?style=flat-square)](https://scrutinizer-ci.com/g/KnpLabs/Gaufrette)
[![Packagist Version](https://img.shields.io/packagist/v/KnpLabs/Gaufrette.svg?style=flat-square)](https://packagist.org/packages/KnpLabs/Gaufrette)
[![Total Downloads](https://img.shields.io/packagist/dt/KnpLabs/Gaufrette.svg?style=flat-square)](https://packagist.org/packages/KnpLabs/Gaufrette)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Join the chat at Gitter](https://img.shields.io/gitter/room/nwjs/nw.js.svg?style=flat-square)](https://gitter.im/KnpLabs/Gaufrette)

Why use Gaufrette?
------------------

Imagine you have to manage a lot of medias in a PHP project. Lets see how to
take this situation in your advantage using Gaufrette.

The filesystem abstraction layer permits you to develop your application without
the need to know where all those medias will be stored and how.

Another advantage of this is the possibility to update the files location
without any impact on the code apart from the definition of your filesystem.
In example, if your project grows up very fast and if your server reaches its
limits, you can easily move your medias in an Amazon S3 server or any other
solution.

### Documentation

Read the official [Gaufrette documentation](http://knplabs.github.io/Gaufrette/).

### Metapackages for adapters

Every maintained adapter now have a dedicated metapackage. You can [find the list on packagist](https://packagist.org/packages/gaufrette/).
**We highly recommend you to use them as they contain their own requirements**: you don't need to worry about third-party dependencies
to install before using Gaufrette anymore.

### Symfony integration

Symfony integration is available through [KnpLabs/KnpGaufretteBundle](https://github.com/KnpLabs/KnpGaufretteBundle).

### Maintainers

Here is the list of dedicated maintainer(s) for every adapter not deprecated. If you don't receive any response to
your issue or pull request in a timely manner, ping us:

| Adapter            | Referent                    |
|--------------------|-----------------------------|
| AsyncAws S3        | @Nyholm                     |
| AwsS3              | @NiR-                       |
| AzureBlobStorage   | @NiR-                       |
| DoctrineDbal       | @pedrotroller, @NicolasNSSM |
| Flysystem          | @nicolasmure                |
| Ftp                | @fabschurt                  |
| GoogleCloudStorage | @AntoineLelaisant           |
| GridFS             | @NiR-                       |
| InMemory           |                             |
| Local              |                             |
| OpenCloud          | @NiR-                       |
| PhpseclibSftp      | @fabschurt                  |
| Zip                |                             |

For `InMemory`, `Local` and `Zip` adapters everyone in this list is considered as a maintainer.

### Development

Requires :
  * docker-ce
  * docker-compose

1) Create `.env` file :
```bash
$ make docker.dev
```
and configure it as you want.

2) Build the php docker image :
```bash
$ make docker.build
```

3) Install dependencies :
```bash
$ make docker.all-deps
```

4) Run tests :
```bash
$ make docker.tests
```

You can also use a different php version, simply set the `PHP_VERSION` env var
to any of these values when calling a make target :
- `7.1`
- `7.2` (default)
- `7.3` (the docker setup for php 7.3 is available, however the ssh2 extension
is not installed [as it is not available for php 7.3 yet](https://serverpilot.io/docs/how-to-install-the-php-ssh2-extension))

See the [`docker-compose.yml`](/docker-compose.yml) file for more details.

You'll need to clear the previously installed dependencies when switching from
a version to an other, to do so, run :
```bash
$ make clear-deps
$ PHP_VERSION=<the_version_you_want_to_use> make build install-deps
```

5) Apply Coding Standards

You should check for CS violations by using
```bash
$ make php-cs-compare
```
and fix them with 
```bash
$ make php-cs-fix
```

### Note

This project does not have any stable release yet but we do not want to break BC now.
