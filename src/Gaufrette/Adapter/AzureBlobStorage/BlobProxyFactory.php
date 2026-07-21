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
    public function __construct(protected string $connectionString)
    {
        if (!class_exists(ServicesBuilder::class) && !class_exists(BlobRestProxy::class)) {
            throw new \LogicException('You need to install package "microsoft/azure-storage-blob" to use this adapter');
        }
    }

    public function create(): BlobRestProxy
    {
        if (class_exists(ServicesBuilder::class)) {
            // for microsoft/azure-storage < 1.0
            return ServicesBuilder::getInstance()->createBlobService($this->connectionString);
        }

        return BlobRestProxy::createBlobService($this->connectionString);
    }
}
