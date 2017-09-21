---
currentMenu: open-stack
---

# OpenStack

First, you will need to install the adapter:
```bash
composer require gaufrette/openstack-adapter
```

To use the OpenStack adapter you will need to create a connection using the
[OpenStack SDK](https://github.com/php-opencloud/openstack).

The OpenStack container to use with the adapter should be created on your own.
You can do it manually from the admin panel of your cloud provider, or
progammatically using the OpenStack SDK :

```php
$objectStore = (new OpenStack([
        // connection options
    ]))
    ->objectStoreV1()
;

/*
 * @see \OpenStack\ObjectStore\v1\Api::putContainer for the list of options
 */
$objectStore->createContainer([
    'name' => 'my-container',
]);
```

## Usage with Identity API v3

For services using the [OpenStack Identity API v3](https://developer.openstack.org/api-ref/identity/v3/index.html),
such as [IBM Cloud](https://www.ibm.com/cloud/) :

```php
use Gaufrette\Adapter\OpenStack as OpenStackAdapter;
use Gaufrette\Filesystem;
use OpenStack\OpenStack;

$objectStore = (new OpenStack([
        'user' => [
            'id' => 'the user ID related to the storage service',
            'password' => 'the user password related to the storage service',
        ],
        'authUrl' => 'https://example.com/v2/identity',
        'region' => 'the cloud region (eg "london")',
    ]))
    ->objectStoreV1()
;

$adapter = new OpenStackAdapter(
    $objectStore,
    'container-name'
);

$filesystem = new Filesystem($adapter);
```

To find the options to use with IBM Cloud, [create a new project](https://console.bluemix.net/developer/appservice/starter-kits)
and add an ObjectStorage to the project. The storage will be configured
automatically and you'll be able to see its service credentials then.

## Usage with Identity API v2

For services using the [OpenStack Identity API v2](https://developer.openstack.org/api-ref/identity/v2/),
such as [rackspace.com](https://www.rackspace.com/) :

```php
use Gaufrette\Adapter\OpenStack as OpenStackAdapter;
use Gaufrette\Filesystem;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use OpenStack\Identity\v2\Service as IdentityService;
use OpenStack\OpenStack;

$objectStore = new (OpenStack([
        'username' => 'your username',
        'password' => 'your password',
        'tenantId' => 'your tenant Id (also known as account Id/number)'
        'authUrl' => 'https://example.com/v2/identity',
        'region' => 'the cloud region (eg "LON" for London)',
        'identityService' => IdentityService::factory(
            new Client([
                'base_uri' => 'https://example.com/v2/identity',
                'handler' => HandlerStack::create(),
            ])
        ),
    ]))
    ->objectStoreV1([
        'catalogName' => 'cloudFiles', // default to "swift", use "cloudFiles" for rackspace,
                                       // or find the catalog name of your cloud service
                                       // associated with the "object-store" catalog type
    ])
;

$adapter = new OpenStackAdapter(
    $objectStore,
    'container-name'
);

$filesystem = new Filesystem($adapter);
```

## Links

- Go [here](https://github.com/php-opencloud/openstack/blob/master/src/OpenStack.php)
to see all OpenStack connection options.
