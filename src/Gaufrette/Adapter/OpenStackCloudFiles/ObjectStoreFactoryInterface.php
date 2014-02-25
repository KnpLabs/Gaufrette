<?php

namespace Gaufrette\Adapter\OpenStackCloudFiles;

use OpenCloud\ObjectStore\Service;

interface ObjectStoreFactoryInterface
{
    /**
     * @return Service
     */
    public function getObjectStore();
}
