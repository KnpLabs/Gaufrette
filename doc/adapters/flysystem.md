---
currentMenu: flysystem
---

# Flysystem

First, you will need to install the adapter:
```bash
composer require gaufrette/flysystem-adapter
```

Folks from [thephpleague](http://thephpleague.com/) have built extraordinary [Flysystem](https://github.com/thephpleague/flysystem) package which does exactly the same thing as Gaufrette, but with slightly different API.

We wanted to make Gaufrette compatible with as many systems as possible, and didn't want to reinvent the wheel.
So we built a Flysystem adapter.

With this adapter you can use any [Flysystem adapter](https://github.com/thephpleague/flysystem#adapters) with no performance penalties. It is just a tiny layer that makes Gaufrette talk to Flysystem adapters.

## Example

We will show using Flysystem adapter on Dropbox example.

First, you need to install Flysystem Dropbox adapter through composer:

```bash
$ composer require league/flysystem-dropbox
```

Now, just wrap Dropbox adapter into Gaufrette Flysystem adapter.

```php
<?php

$adapter = new Gaufrette\Adapter\Flysystem(
    new League\Flysystem\Dropbox\DropboxAdapter(
        new Dropbox\Client('<token>', '<consumer_secret>')
    )
);

$filesystem = new Gaufrette\Filesystem($adapter);
```

As said above, same pattern can be applied to any [Flysystem adapter](https://github.com/thephpleague/flysystem#adapters).
