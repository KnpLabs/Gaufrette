---
currentMenu: implementing-new-adapter
---

# Implementing new Adapter

Let's say we want to support new storage system with existing Graufette API.
The way to do it is by implementing a new adapter.

We will illustrate this by implementing example adapter for KnpStorage file system.

## Spec BDD

We encourage contributors to start with describing a new adapter:

```bash
$ ./bin/phpspec describe Gaufrette/Adapter/KnpStorage
Specification for Gaufrette\Adapter\KnpStorage created in Gaufrette/spec/Gaufrette/Adapter/KnpStorageSpec.php.
```

We describe how our adapter is instantated and make sure it implements `Gaufrette\Adapter` interface:

```php
<?php

class KnpStorageSpec extends ObjectBehavior
{
    function let(KnpStorage $storage)
    {
        $this->beConstructedWith($storage);
    }

    function it_is_adapter()
    {
        $this->shouldImplement('Gaufrette\Adapter');
    }
}

```

To get the benefits of PHPSpec code generator we run:

```bash
$ ./bin/phpspec run spec/Gaufrette/Adapter/KnpStorageSpec.php
```

You can continue to play with PHPSpec, read more on [PHPSpec website](http://phpspec.readthedocs.org/en/latest/).

## Implementing adapter interface

Now all we need to do is to make sure `KnpStorage` implements all Gaufrette `Adapterinterface` methods.

## Contibute it

Once you are sure your adapter is ready, share it with awesome Gaufrette community by submitting a pull request.

Thank you for doing this, you are awesome!
