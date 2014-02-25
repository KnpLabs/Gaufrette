<?php
namespace Gaufrette\Adapter;

use Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface;
use OpenCloud\OpenStack;

/**
 * Class LazyOpenStackCloudFiles
 * @package Gaufrette\Adapter
 * @author  Chris Warner <cdw.lighting@gmail.com>
 * @deprecated in favor of LazyOpenCloud
 */
class LazyOpenStackCloudFiles extends OpenCloud
{
    /**
     * @var ConnectionFactoryInterface
     */
    protected $connectionFactory;

    /**
     * @param ConnectionFactoryInterface $connectionFactory
     * @param string                     $containerName
     * @param bool                       $createContainer
     */
    public function __construct($connectionFactory, $containerName, $createContainer = false)
    {
        $this->connectionFactory = $connectionFactory;
        $this->containerName     = $containerName;
        $this->createContainer   = $createContainer;
    }

    /**
     * Override parent to lazy-load object store
     *
     * {@inheritdoc}
     */
    protected function getContainer()
    {
        if (!$this->objectStore) {
            $this->objectStore = $this->connectionFactory->create()->objectStoreService('cloudFiles', null, 'publicURL');
        }

        return parent::getContainer();
    }
}
