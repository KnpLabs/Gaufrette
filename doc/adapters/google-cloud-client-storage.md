---
currentMenu: google-cloud-client-storage
---

# Google Cloud Client Storage

This adapter requires an instance of Google\Cloud\Storage\StorageClient that has proper access rights to the bucket you want to use.

For more details see:
http://googlecloudplatform.github.io/google-cloud-php/
https://console.cloud.google.com/

In order to get started:

1) Create a project in [Google Cloud Platform](https://console.cloud.google.com/).
2) Create a bucket for the project in Storage.
3) Create a Service Account in IAM & Admin section that can write access the bucket, download its key.json file.

**At all times make sure you keep your key.json file private and nobody can access it from the Internet.**

## Example

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\GoogleCloudClientStorage;

$storage = new StorageClient(array(
    'projectId'     => 'your-project-id',
    'keyFilePath'   => 'path/to/your/project/key.json'
));

# You can optionally set the directory in the bucket and the acl permissions for all uploaded files...
# By default Cloud Storage applies the bucket's default object ACL to the object (uploaded file).
# The example below gives read access to the uploaded files to anyone in the world
# Note that the public URL of the file IS NOT the bucket's file url,
# see https://cloud.google.com/storage/docs/access-public-data for details

$adapter = new GoogleCloudClientStorage($storage, 'bucket_name',
    array(
        'directory' => 'bucket_directory',
        'acl'       => array(
            'allUsers' => \Google\Cloud\Storage\Acl::ROLE_READER
        )
    )
);

$key = 'myAmazingFile.txt';

$filesystem = new Filesystem($adapter);

$filesystem->write($key, 'Uploaded at: '.date('Y-m-d @ H:i:s'), true);

# optional
$adapter->setMetadata($key,
    array(
        'FileDescription' => 'This is my file. There are many like it, but this one is mine.'
    )
);
```

Here you can find some more info regarding ACL:
* [Creating and Managing Access Control Lists (ACLs)](https://cloud.google.com/storage/docs/access-control/create-manage-lists)
* [Access Control Lists (ACLs)](https://cloud.google.com/storage/docs/access-control/lists)
