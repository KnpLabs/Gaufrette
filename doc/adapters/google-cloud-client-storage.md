---
currentMenu: google-cloud-client-storage
---

# Google Cloud Client Storage

This adapter requires an instance of Google\Cloud\Storage\StorageClient that has proper access rights to the bucket you want to use.

For more details see:
http://googlecloudplatform.github.io/google-cloud-php/
https://console.cloud.google.com/

## Example

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\GoogleCloudClientStorage;

$storage = new StorageClient(array(
    'projectId'     => 'your-project-id',
    'keyFilePath'   => 'path/to/your/project/key.json'
));

# you can optionally set the directory in the bucket and the acl permissions for all uploaded files...
# by default the uploaded files are read/write by the owner only
# the example below gives read access to the uploaded files to anyone in the world
 
$adapter = new GoogleCloudClientStorage($storage, 'bucket_name',
    array(
        'directory' => 'bucket_directory',
        'acl'       => array(
            'allUsers' => \Google\Cloud\Storage\Acl::ROLE_READER
        )
    )
);

$key = 'myAmazingFile.txt';

# optional
$adapter->setMetadata($key,
    array(
        'FileDescription' => 'This is my file. There are many like it, but this one is mine.'
    )
);

$filesystem = new Filesystem($adapter);

$filesystem->write($key, 'Uploaded at: '.date('Y-m-d @ H:i:s'), true);

```