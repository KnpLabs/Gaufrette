<?php

namespace Gaufrette\Adapter\OpenStackCloudFiles;

use OpenCloud\OpenStack;

class OpenStackObjectStoreFactory implements ObjectStoreFactoryInterface
{
    /**
     * @var OpenStack
     */
    protected $connection;

    /**
     * @var string
     */
    protected $region;

    /**
     * @var string
     */
    protected $urlType;

    /**
     * Constructor
     *
     * @param OpenStack $connection
     * @param string $region
     * @param string $urlType
     */
    public function __construct(OpenStack $connection, $region, $urlType)
    {
        $this->connection = $connection;
        $this->region = $region;
        $this->urlType = $urlType;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectStore()
    {
        return $this->connection->objectStoreService('cloudFiles', $this->region, $this->urlType);
    }
}
