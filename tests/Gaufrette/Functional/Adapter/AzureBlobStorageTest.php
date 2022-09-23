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

    protected function setUp(): void
    {
        $account = getenv('AZURE_ACCOUNT');
        $key = getenv('AZURE_KEY');
        $containerName = getenv('AZURE_CONTAINER');

        if (empty($account) || empty($key) || empty($containerName)) {
            $this->markTestSkipped('Either AZURE_ACCOUNT, AZURE_KEY and/or AZURE_CONTAINER env variables are not defined.');
        }

        $connection = sprintf('DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s;EndpointSuffix=core.windows.net', $account, $key);

        $this->container = uniqid($containerName);
        $this->adapter = new AzureBlobStorage(new BlobProxyFactory($connection), $this->container, true);
        $this->filesystem = new Filesystem($this->adapter);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetContentType(): void
    {
        $path = '/somefile';
        $content = 'Some content';
        $this->filesystem->write($path, $content);

        $this->assertEquals('text/plain', $this->filesystem->mimeType($path));
    }

    protected function tearDown(): void
    {
        if ($this->adapter === null) {
            return;
        }

        $this->adapter->deleteContainer($this->container);
    }
}
