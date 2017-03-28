---
currentMenu: aws-s3
---

# AWS S3

First, you will need to install AWS SDK for PHP:
```bash
composer require aws/aws-sdk-php
```

In order to use this adapter you'll need an access key and a secret key. 
We **strongly recommend** you to create a dedicated IAM user with the most restrictive policy 
(in such case you can remove `s3:CreateBucket` action):

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:CreateBucket",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::bucket_name"
            ]
        },
        {
            "Effect": "Allow",
            "Action": [
                "s3:PutObject",
                "s3:GetObject",
                "s3:DeleteObject"
            ],
            "Resource": [
                "arn:aws:s3:::bucket_name/*"
            ]
        }
    ]
}
```

You can even skip `s3:CreateBucket` role if you create your bucket first, which is also recommended 
for production environment.

## Example

```php
<?php

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3 as AwsS3Adapter;
use Gaufrette\Filesystem;

// For aws-sdk-php v3
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
