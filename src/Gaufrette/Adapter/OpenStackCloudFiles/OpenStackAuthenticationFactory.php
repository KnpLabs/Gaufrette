<?php

namespace Gaufrette\Adapter\OpenStackCloudFiles;


use OpenCloud\OpenStack;

/**
 * Class OpenStackAuthenticationFactory
 * @package Gaufrette\Adapter\OpenStackCloudFiles
 * @author  Chris Warner <cdw.lighting@gmail.com>
 * @deprecated in favor of ObjectStoreFactory
 */
class OpenStackAuthenticationFactory extends BaseOpenStackAuthenticationFactory implements ConnectionFactoryInterface
{
    /**
     * @return OpenStack
     */
    public function create()
    {
        if (!$this->authenciationService) {
            $this->authenciationService = new OpenStack($this->url, array ($this->username, $this->apikey));
            $this->authenciationService->getUser()->setDefaultRegion($this->region);
        }

        return $this->authenciationService;
    }
}
