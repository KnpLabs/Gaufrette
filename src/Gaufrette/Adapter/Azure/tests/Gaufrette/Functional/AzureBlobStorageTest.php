<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\Azure\BlobProxyFactory;
use Gaufrette\Adapter\Azure\BlobStorage;

class AzureBlobStorageTest extends FunctionalTestCase
{
    public function setUp()
    {
        $adapter = $this->getAdapter();
        $this->filesystem = new Filesystem($adapter);
    }

    private function getAdapter()
    {
        $connectionString   = 'BlobEndpoint=http://XXXXXXXXXXX.blob.core.windows.net/;AccountName=XXXXXXXXXXXX;AccountKey=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
        $containerName      = 'gaufrette-test';
        $create             = true;

        $factory = new BlobProxyFactory($connectionString);
        return new BlobStorage($factory, $containerName, $create);
    }
}
