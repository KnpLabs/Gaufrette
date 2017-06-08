<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\AzureBlobStorage;
use Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactory;
use Gaufrette\Filesystem;

/**
 * Class AzureBlobStorageTest
 * @group AzureBlobStorage
 */
class AzureBlobStorageTest extends FunctionalTestCase
{
    public function setUp()
    {
        $key = getenv('AZURE_KEY');
        $secret = getenv('AZURE_SECRET');
        $containerName = getenv('AZURE_CONTAINER');
        if (empty($key) || empty($secret) || empty($containerName)) {
            $this->markTestSkipped('Either AZURE_KEY, AZURE_SECRET and/or AZURE_CONTAINER env variables are not defined.');
        }

        $connection = sprintf('BlobEndpoint=http://%1$s.blob.core.windows.net/;AccountName=%1$s;AccountKey=%2$s', $key, $secret);

        $this->filesystem = new Filesystem(new AzureBlobStorage(new BlobProxyFactory($connection), $containerName, true));
    }
}
