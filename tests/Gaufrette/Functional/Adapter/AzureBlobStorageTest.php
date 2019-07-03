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

    /** @var \MicrosoftAzure\Storage\Blob\Internal\IBlob */
    private $blobProxy;

    public function setUp()
    {
        $account = getenv('AZURE_ACCOUNT');
        $key = getenv('AZURE_KEY');
        $containerName = getenv('AZURE_CONTAINER');

        if (empty($account) || empty($key) || empty($containerName)) {
            $this->markTestSkipped('Either AZURE_ACCOUNT, AZURE_KEY and/or AZURE_CONTAINER env variables are not defined.');
        }

        $connection = sprintf('BlobEndpoint=http://%1$s.blob.core.windows.net/;AccountName=%1$s;AccountKey=%2$s', $account, $key);

        $blobProxyFactory = new BlobProxyFactory($connection);
        $this->blobProxy = $blobProxyFactory->create();

        $this->container = uniqid($containerName);
        $this->blobProxy->createContainer($this->container);

        $this->adapter = new AzureBlobStorage($blobProxyFactory, $this->container);
        $this->filesystem = new Filesystem($this->adapter);
    }

    public function tearDown()
    {
        if ($this->adapter === null) {
            return;
        }

        $this->blobProxy->deleteContainer($this->container);
    }

    /**
     * @test
     * @group functional
     * @expectedException \Gaufrette\Exception\StorageFailure
     */
    public function shouldThrowWhenUsingAnUnexistingContainer()
    {
        $this->blobProxy->deleteContainer($this->container);

        $this->filesystem->write('foo', 'Some content');

        // will not delete the container again, see self::tearDown function
        $this->adapter = null;
    }
}
