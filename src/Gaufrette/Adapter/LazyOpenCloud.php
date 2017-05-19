<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter\OpenStackCloudFiles\ObjectStoreFactoryInterface;

@trigger_error('The '.__NAMESPACE__.'\LazyOpenCloud adapter is deprecated since version 0.4 and will be removed in 1.0. Use the OpenCloud adapter instead.', E_USER_DEPRECATED);

/**
 * LazyOpenCloud.
 *
 * @author  Daniel Richter <nexyz9@gmail.com>
 *
 * @deprecated The LazyOpenCloud adapter is deprecated since version 0.4 and will be removed in 1.0. Use the OpenCloud adapter instead.
 */
class LazyOpenCloud extends OpenCloud
{
    /**
     * @var ObjectStoreFactoryInterface
     */
    protected $objectStoreFactory;

    /**
     * @param ObjectStoreFactoryInterface $objectStoreFactory
     * @param string                      $containerName
     * @param bool                        $createContainer
     */
    public function __construct(ObjectStoreFactoryInterface $objectStoreFactory, $containerName, $createContainer = false)
    {
        $this->objectStoreFactory = $objectStoreFactory;
        $this->containerName = $containerName;
        $this->createContainer = $createContainer;
    }

    /**
     * Override parent to lazy-load object store.
     *
     * {@inheritdoc}
     */
    protected function getContainer()
    {
        if (!$this->objectStore) {
            $this->objectStore = $this->objectStoreFactory->getObjectStore();
        }

        return parent::getContainer();
    }
}
