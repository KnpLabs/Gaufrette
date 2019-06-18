<?php

namespace Gaufrette\Adapter\AzureBlobStorage;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\ServicesBuilder;

/**
 * Basic implementation for a Blob proxy factory.
 *
 * @author Luciano Mammino <lmammino@oryzone.com>
 */
class BlobProxyFactory implements BlobProxyFactoryInterface
{
    /**
     * @var string
     */
    protected $connectionString;

    /**
     * @param string $connectionString
     */
    public function __construct($connectionString)
    {
        $this->connectionString = $connectionString;
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        if (class_exists(ServicesBuilder::class)) {
            // for microsoft/azure-storage < 1.0
            return ServicesBuilder::getInstance()->createBlobService($this->connectionString);
        }

        return BlobRestProxy::createBlobService($this->connectionString);
    }
}
