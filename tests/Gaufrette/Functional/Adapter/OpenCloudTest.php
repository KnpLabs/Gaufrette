<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\OpenCloud;
use Gaufrette\Filesystem;
use OpenCloud\Rackspace;

class OpenCloudTest extends FunctionalTestCase
{
    /** @var \OpenCloud\ObjectStore\Service */
    private $objectStore;

    /** @var string */
    private $container;

    public function setUp()
    {
        $username = getenv('RACKSPACE_USER') ?: '';
        $apiKey = getenv('RACKSPACE_APIKEY') ?: '';
        $container = getenv('RACKSPACE_CONTAINER') ?: '';

        if (empty($username) || empty($apiKey) || empty($container)) {
            $this->markTestSkipped('Either RACKSPACE_USER, RACKSPACE_APIKEY and/or RACKSPACE_CONTAINER env vars are missing.');
        }

        $connection = new Rackspace('https://identity.api.rackspacecloud.com/v2.0/', [
            'username' => $username,
            'apiKey' => $apiKey,
        ]);

        $this->container = uniqid($container);
        $this->objectStore = $connection->objectStoreService('cloudFiles', 'IAD', 'publicURL');
        $this->objectStore->createContainer($this->container);

        $adapter = new OpenCloud($this->objectStore, $this->container);
        $this->filesystem = new Filesystem($adapter);
    }

    public function tearDown()
    {
        if ($this->filesystem === null) {
            return;
        }

        $this->objectStore->getContainer($this->container)->delete(true);
    }
}
