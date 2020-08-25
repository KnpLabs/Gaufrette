---
currentMenu: async-aws-s3
---

# AsyncAws S3

First, you will need to install the simple S3 client:
```bash
composer require async-aws/simple-s3
```

In order to use this adapter you'll need an access key and a secret key.

## Example

```php
<?php

use AsyncAws\SimpleS3\SimpleS3Client;
use Gaufrette\Adapter\AsyncAwsS3 as AwsS3Adapter;
use Gaufrette\Filesystem;

$s3client = new SimpleS3Client([
    'accessKeyId'     => 'your_key_here',
    'accessKeySecret'  => 'your_secret',
    'region'  => 'eu-west-1',
]);

$adapter = new AwsS3Adapter($s3client, 'your-bucket-name');
$filesystem = new Filesystem($adapter);
```
