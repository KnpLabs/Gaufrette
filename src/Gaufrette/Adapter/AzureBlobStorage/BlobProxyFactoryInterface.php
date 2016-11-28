<?php

namespace Gaufrette\Adapter\AzureBlobStorage;

/**
 * Interface to define Blob proxy factories.
 *
 * @author Luciano Mammino <lmammino@oryzone.com>
 */
interface BlobProxyFactoryInterface
{
    /**
     * Creates a new instance of the Blob proxy.
     *
     * @return \MicrosoftAzure\Storage\Blob\Internal\IBlob
     */
    public function create();
}
