---
currentMenu: aws-s3
---

# AWS S3

First, you will need to install the adapter:
```bash
composer require gaufrette/aws-s3-adapter
```

If you want a specific version of AWS SDK (both v2 and v3 are supported), you can require it:
```bash
composer require aws/aws-sdk-php
```

## Example

```php
<?php

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3 as AwsS3Adapter;
use Gaufrette\Filesystem;

$s3client = new S3Client([
    'credentials' => array(
        'key'     => 'your_key_here',
        'secret'  => 'your_secret',
    ),
    'version' => 'latest',
    'region'  => 'eu-west-1',
]);
$adapter = new AwsS3Adapter($s3client,'your-bucket-name');
$filesystem = new Filesystem($adapter);
```
