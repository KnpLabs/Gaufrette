---
currentMenu: google-cloud-storage
---

# GoogleCloudStorage

To use the GoogleCloudStorage adapter you will need to create a connection using the [Google APIs Client Library for PHP]
(https://github.com/google/google-api-php-client) and create a Client ID/Service Account in your [Developers Console]
(https://console.developers.google.com/). You can then create the `\Google\Service\Storage` which is required for the
GoogleCloudStorage adapter.

## Example

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter\GoogleCloudStorage;

$keyFileLocation = '/home/me/path/to/service-auth-key.json';
$bucketName = 'gaufrette-bucket-test-' . uniqid();
$projectId = 'your-project-id-000';
$bucketLocation = 'EUROPE-WEST9';

putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $keyFileLocation);

$client = new \Google\Client();
$client->setApplicationName('Gaufrette');
$client->addScope(Google\Service\Storage::DEVSTORAGE_FULL_CONTROL);
$client->useApplicationDefaultCredentials();

$service = new \Google\Service\Storage($client);
$adapter = new GoogleCloudStorage(
    $service,
    $bucketName,
    [
        // Options to set for automatic creation of the bucket
        GoogleCloudStorage::OPTION_CREATE_BUCKET_IF_NOT_EXISTS => true,
        GoogleCloudStorage::OPTION_PROJECT_ID => $projectId,
        GoogleCloudStorage::OPTION_LOCATION => $bucketLocation,
    ],
    true
);

$filesystem = new Gaufrette\Filesystem($adapter);
```
