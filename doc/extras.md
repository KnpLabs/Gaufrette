---
currentMenu: extras
---

# Gaufrette Extras

Some extra features built on top of Gaufette live in a package separate from the core. It provides:
  
  * **Resolvable filesystem**: introduce `resolve()` method to transform an object path into a URI.

In order to install it:

    composer require gaufrette/extras


## Resolvable filesystem

Filesystem decorator providing `resolve()` method to resolve an object path into URI. It uses a resolver 
(implementing `ResolverInterface`) to do the resolution.

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Extras\Resolvable\ResolvableFilesystem;
use Gaufrette\Extras\Resolvable\Resolver\AwsS3PublicUrlResolver;

$client     = // AwsS3 client instantiation
$decorated  = new Filesystem(new AwsS3($client, 'my_bucket', ['directory' => 'root/dir']));
$filesystem = new ResolvableFilesystem(
    $decorated,
    new AwsS3PublicUrlResolver($client, 'my_bucket', 'root/dir')
);

// should return something like "https://eu-west-1.blabla.aws.com/my_bucket/root/dir/foo/bar.png?token
var_dump($filesystem->resolve('foo/bar.png'));
```

Currently, the following resolvers are implemented. All can be found in the `Gaufrette\Extras\Resolvable\Resolver` namespace:

* **AwsS3PublicUrlResolver**: Create a URL for an object stored on S3 with public ACL.
* **AwsS3PresignedUrlResolver**: Create a temporary URL, valid for a given amount of time. Useful when you have to share object(s) with private ACL.
* **StaticUrlResolver**: Resolves the object into an URL by concatenating a prefix with object pah.
