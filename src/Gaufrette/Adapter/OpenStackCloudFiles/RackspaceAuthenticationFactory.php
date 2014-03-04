<?php
namespace Gaufrette\Adapter\OpenStackCloudFiles;

use OpenCloud\OpenStack;
use Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface;
use OpenCloud\Common\Base;
use OpenCloud\Rackspace;

/**
 * Class RackspaceAuthenticationConnectionFactory
 * @package Gaufrette\Adapter\OpenStackCloudFiles
 * @author  Chris Warner <cdw.lighting@gmail.com>
 * @deprecated in favor of OpenStackObjectStoreFactory
 */
class RackspaceAuthenticationFactory extends BaseOpenStackAuthenticationFactory implements ConnectionFactoryInterface
{

    /**
     * @return Rackspace
     */
    public function create()
    {
        if (!$this->authenciationService) {
            $this->authenciationService = new Rackspace($this->url, array ($this->username, $this->apikey));
            $this->authenciationService->getUser()->setDefaultRegion($this->region);
        }

        return $this->authenciationService;
    }
}
