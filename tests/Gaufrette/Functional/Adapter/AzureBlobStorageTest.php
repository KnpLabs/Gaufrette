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
    /** @var string Name of the Azure container used */
    private $container;

    /** @var AzureBlobStorage */
    private $adapter;

    protected function setUp()
    {
        $account = getenv('AZURE_ACCOUNT');
        $key = getenv('AZURE_KEY');
        $containerName = getenv('AZURE_CONTAINER');

        if (empty($account) || empty($key) || empty($containerName)) {
            $this->markTestSkipped('Either AZURE_ACCOUNT, AZURE_KEY and/or AZURE_CONTAINER env variables are not defined.');
        }

        $connection = sprintf('BlobEndpoint=http://%1$s.blob.core.windows.net/;AccountName=%1$s;AccountKey=%2$s', $account, $key);

        $this->container = uniqid($containerName);
        $this->adapter = new AzureBlobStorage(new BlobProxyFactory($connection), $this->container, true);
        $this->filesystem = new Filesystem($this->adapter);
    }

    protected function tearDown()
    {
        if ($this->adapter === null) {
            return;
        }

        $this->adapter->deleteContainer($this->container);
    }
}
