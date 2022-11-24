<?php

$basedir = __DIR__ . '/';

$return = [
    'parameters' => [
        'ignoreErrors' => [
            ['message' => '#^Call to function is_resource\(\) with string will always evaluate to false\.$#',
                'count' => 2,
                'path' => $basedir . 'src/Gaufrette/Adapter/AwsS3.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\AwsS3\\:\\:write\(\) should return bool\|int but returns string\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AwsS3.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\AwsS3\\:\\:size\(\) should return int but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AwsS3.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\AwsS3\\:\\:mimeType\(\) should return string but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AwsS3.php',

            ],
            ['message' => '#^Parameter \\$options of method Gaufrette\\\Adapter\\\AzureBlobStorage\\:\\:deleteContainer\(\) has invalid typehint type MicrosoftAzure\\\Storage\\\Blob\\\Models\\\DeleteContainerOptions\.$#',
                'count' => 2,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Parameter \#2 \\$options of method MicrosoftAzure\\\Storage\\\Blob\\\Internal\\\IBlob\\:\\:deleteContainer\(\) expects MicrosoftAzure\\\Storage\\\Blob\\\Models\\\BlobServiceOptions\|null, MicrosoftAzure\\\Storage\\\Blob\\\Models\\\DeleteContainerOptions\|null given\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Parameter \#4 \\$options of method MicrosoftAzure\\\Storage\\\Blob\\\Internal\\\IBlob\\:\\:createBlockBlob\(\) expects MicrosoftAzure\\\Storage\\\Blob\\\Models\\\CreateBlockBlobOptions\|null, MicrosoftAzure\\\Storage\\\Blob\\\Models\\\CreateBlobOptions given\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Call to function is_resource\(\) with string will always evaluate to false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\AzureBlobStorage\\:\\:write\(\) should return bool\|int but returns string\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Parameter \#2 \\$prefix of method Gaufrette\\\Adapter\\\AzureBlobStorage\\:\\:fetchBlobs\(\) expects null, string given\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\AzureBlobStorage\\:\\:size\(\) should return int but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\AzureBlobStorage\\:\\:mimeType\(\) should return string but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\AzureBlobStorage\\:\\:checksum\(\) should return string but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Cannot call method getBody\(\) on string\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Strict comparison using \\!\\=\\= between null and null will always evaluate to false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/AzureBlobStorage.php',

            ],
            ['message' => '#^Parameter \#1 \\$ftp of function .+ expects FTP\\\Connection, resource(\|null)? given\.$#',
                'count' => 15,
                'path' => $basedir . 'src/Gaufrette/Adapter/Ftp.php',

            ],
            ['message' => '#^PHPDoc tag @throws with type Gaufrette\\\Adapter\\\RuntimeException is not subtype of Throwable$#',
                'count' => 3,
                'path' => $basedir . 'src/Gaufrette/Adapter/Ftp.php',

            ],
            ['message' => '#^Call to function is_resource\(\) with string will always evaluate to false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/GoogleCloudStorage.php',

            ],
            ['message' => '#^Property Gaufrette\\\Adapter\\\GridFS\\:\\:\\$bucket has unknown class MongoDB\\\GridFS\\\Bucket as its type\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Parameter $bucket of method Gaufrette\\\Adapter\\\GridFS\\:\\:__construct\(\) has invalid typehint type MongoDB\\\GridFS\\\Bucket\.$#',
                'count' => 2,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Call to method openDownloadStreamByName\(\) on an unknown class MongoDB\\\GridFS\\\Bucket\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Caught class MongoDB\\\GridFS\\\Exception\\\FileNotFoundException not found\.$#',
                'count' => 2,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Call to method openUploadStream\(\) on an unknown class MongoDB\\\GridFS\\\Bucket\.$#',
                'count' => 2,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Unreachable statement - code above always terminates\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Call to method downloadToStreamByName\(\) on an unknown class MongoDB\\\GridFS\\\Bucket\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Call to method findOne\(\) on an unknown class MongoDB\\\GridFS\\\Bucket\.$#',
                'count' => 6,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Call to method find\(\) on an unknown class MongoDB\\\GridFS\\\Bucket\.$#',
                'count' => 2,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Call to method delete\(\) on an unknown class MongoDB\\\GridFS\\\Bucket\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\GridFS\\:\\:size\(\) should return int but returns false\.$#',
                'count' => 2,
                'path' => $basedir . 'src/Gaufrette/Adapter/GridFS.php',

            ],
            ['message' => '#^PHPDoc tag @param has invalid value \(\\$path\)\\: Unexpected token "\\$path", expected type at offset 74$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/Local.php',

            ],
            ['message' => '#^PHPDoc tag @param has invalid value \(string The directory\'s path to delete\)\\: Unexpected token "The", expected variable at offset 25$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/Local.php',

            ],
            ['message' => '#^If condition is always true\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/OpenCloud.php',

            ],
            ['message' => '#^Unreachable statement \\\- code above always terminates\.$#',
                'count' => 2,
                'path' => $basedir . 'src/Gaufrette/Adapter/OpenCloud.php',

            ],
            ['message' => '#^Call to an undefined method OpenCloud\\\Common\\\Base\\:\\:getName\(\)\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/OpenCloud.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\OpenCloud\\:\\:mtime\(\) should return bool\|int but returns string\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/OpenCloud.php',

            ],
            ['message' => '#^Method Gaufrette\\\Adapter\\\OpenCloud\\:\\:checksum\(\) should return string but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/OpenCloud.php',

            ],
            ['message' => '#^Constant NET_SFTP_TYPE_DIRECTORY not found\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/PhpseclibSftp.php',

            ],
            ['message' => '#^PHPDoc tag @param has invalid value \(\\$key\)\\: Unexpected token "\\$key", expected type at offset 161$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/Zip.php',

            ],
            ['message' => '#^If condition is always true\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Adapter/Zip.php',

            ],
            ['message' => '#^PHPDoc tag @param has invalid value \(\\$key\)\\: Unexpected token "\\$key", expected type at offset 82$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/FilesystemInterface.php',

            ],
            ['message' => '#^PHPDoc tag @param has invalid value \(\\$key\)\\: Unexpected token "\\$key", expected type at offset 68$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/FilesystemInterface.php',

            ],
            ['message' => '#^Method Gaufrette\\\Stream\\\InMemoryBuffer\\:\\:stat\(\) should return array but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Stream/InMemoryBuffer.php',

            ],
            ['message' => '#^Method Gaufrette\\\Stream\\\Local\\:\\:read\(\) should return string but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Stream/Local.php',

            ],
            ['message' => '#^Method Gaufrette\\\Stream\\\Local\\:\\:write\(\) should return int but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Stream/Local.php',

            ],
            ['message' => '#^Method Gaufrette\\\Stream\\\Local\\:\\:tell\(\) should return int but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Stream/Local.php',

            ],
            ['message' => '#^Method Gaufrette\\\Stream\\\Local\\:\\:stat\(\) should return array but returns false\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Stream/Local.php',

            ],
            ['message' => '#^Parameter \#2 \\\$callback of function array_filter expects callable\(mixed, mixed\)\\: bool, \'strlen\' given\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Util/Path.php',

            ],
            ['message' => '#^Method Gaufrette\\\Util\\\Size\\:\\:fromResource\(\) should return string but returns int\.$#',
                'count' => 1,
                'path' => $basedir . 'src/Gaufrette/Util/Size.php',

            ],
        ],
    ],
];

// "league/flysystem": "^1.0"
if (!interface_exists(\League\Flysystem\FilesystemAdapter::class)) {
    $return['parameters']['ignoreErrors'] = array_merge($return['parameters']['ignoreErrors'], [
        ['message' => '#^Property Gaufrette\\\Adapter\\\Flysystem\\:\\:\\$config has unknown class Gaufrette\\\Adapter\\\Config as its type\.$#',
            'count' => 1,
            'path' => $basedir . 'src/Gaufrette/Adapter/Flysystem.php',

        ],
        ['message' => '#^Parameter $config of method Gaufrette\\\Adapter\\\Flysystem\\:\\:__construct\(\) has invalid typehint type Gaufrette\\\Adapter\\\Config\.$#',
            'count' => 1,
            'path' => $basedir . 'src/Gaufrette/Adapter/Flysystem.php',

        ],
        ['message' => '#^Property Gaufrette\\\Adapter\\\Flysystem\\:\\:\\$config \(Gaufrette\\\Adapter\\\Config\) does not accept League\\\Flysystem\\\Config\.$#',
            'count' => 1,
            'path' => $basedir . 'src/Gaufrette/Adapter/Flysystem.php',

        ],
        ['message' => '#^Parameter \#3 \\$config of method League\\\Flysystem\\\AdapterInterface\\:\\:write\(\) expects League\\\Flysystem\\\Config, Gaufrette\\\Adapter\\\Config given\.$#',
            'count' => 1,
            'path' => $basedir . 'src/Gaufrette/Adapter/Flysystem.php',

        ],
    ]);

    $return['parameters']['excludePaths']['analyse'] = [
        $basedir . 'src/Gaufrette/Adapter/FlysystemV2V3.php',
    ];
} else {
    $return['parameters']['excludePaths']['analyse'] = [
        $basedir . 'src/Gaufrette/Adapter/Flysystem.php',
    ];
}

return $return;
