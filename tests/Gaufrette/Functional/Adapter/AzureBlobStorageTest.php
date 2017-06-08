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
        $account = getenv('AZURE_ACCOUNT');
        $key = getenv('AZURE_KEY');
        $containerName = getenv('AZURE_CONTAINER');

        if (empty($account) || empty($key) || empty($containerName)) {
            $this->markTestSkipped('Either AZURE_ACCOUNT, AZURE_KEY and/or AZURE_CONTAINER env variables are not defined.');
        }

        $connection = sprintf('BlobEndpoint=http://%1$s.blob.core.windows.net/;AccountName=%1$s;AccountKey=%2$s', $account, $key);

        $this->filesystem = new Filesystem(new AzureBlobStorage(new BlobProxyFactory($connection), $containerName, true));
    }
}
