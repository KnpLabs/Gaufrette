---
currentMenu: azure-blob-storage
---

# AzureBlobStorage

Azure Blob Storage is the storage service provided by Microsoft Windows Azure cloud environment. First, you will need to install the adapter:
```bash
composer require gaufrette/azure-blob-storage-adapter
```

To instantiate the `AzureBlobStorage` adapter you need a `BlobProxyFactoryInterface` instance (you can use the default
`BlobProxyFactory` class) and a connection string. The connection string should follow this prototype:

    BlobEndpoint=https://XXXXXXXXXX.blob.core.windows.net/;AccountName=XXXXXXXX;AccountKey=XXXXXXXXXXXXXXXXXXXX

You should be able to find your **endpoint**, **account name** and **account key** in your
[Windows Azure management console](https://manage.windowsazure.com).

Thanks to the blob proxy factory, the adapter lazy loads the connection to the endpoint, so it will not create any
connection until it's really needed (eg. when a read or write operation is issued).

## Multi-container mode

If you specify a container name, adapter will use only that container for all blobs.

If you omit specifying a container, it will use a so-called multi-container mode in which container name is determined
directly from key. This allows for more flexibility if you're using dedicated storage accounts per asset type
(ie. one for images, one for videos) as you get to group assets logically, use container-level privileges, etc.

## Example

```php
<?php

$connectionString = '...';
$factory = new Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactory($connectionString);

// single-container mode
$adapter = new Gaufrette\Adapter\AzureBlobStorage($factory, 'my-container');
$filesystem = new Gaufrette\Filesystem($adapter);
// container=my-container, path=my/stuff.txt
$filesystem->write('my/stuff.txt', 'This is my stuff');

// multi-container mode
$adapter = new Gaufrette\Adapter\AzureBlobStorage($factory);
// make auto-created containers public by default
$containerOptions = new MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
$containerOptions->setPublicAccess(true);
$adapter->setCreateContainerOptions($containerOptions);
$filesystem = new Gaufrette\Filesystem($adapter);
// container=my (auto-created), path=stuff.txt
$filesystem->write('my/stuff.txt', 'This is my stuff');

```
