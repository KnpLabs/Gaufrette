<?php

namespace Gaufrette\Adapter\AzureBlobStorage;

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
        return ServicesBuilder::getInstance()->createBlobService($this->connectionString);
    }
}
