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

In order to use this adapter you'll need an access key and a secret key.

## Example

```php
<?php

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3 as AwsS3Adapter;
use Gaufrette\Filesystem;

// For aws-sdk-php v3
$s3client = new S3Client([
    'credentials' => [
        'key'     => 'your_key_here',
        'secret'  => 'your_secret',
    ],
    'version' => 'latest',
    'region'  => 'eu-west-1',
]);
// For aws-sdk-php v2
$s3client = S3Client::factory([
    'key'     => 'your_key_here',
    'secret'  => 'your_secret',
    'version' => '2006-03-01',
    'region'  => 'eu-west-1',
]);
$adapter = new AwsS3Adapter($s3client,'your-bucket-name');
$filesystem = new Filesystem($adapter);
```

## IAM policy

If you are not familiar with AWS, here are the key concepts:
* [IAM, stands for *Identity and Access Management*](http://docs.aws.amazon.com/IAM/latest/UserGuide/introduction.html)
* [IAM policies, are the way to grant access to your IAM users/groups](http://docs.aws.amazon.com/IAM/latest/UserGuide/introduction_access-management.html)
 
We **strongly recommend** you to create a dedicated IAM user with the most restrictive policy.

You can even skip `s3:CreateBucket` role if you manually create your bucket first, which is also recommended 
for production environment.

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
                "s3:PutObjectAcl",
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
