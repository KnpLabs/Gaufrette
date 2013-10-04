<?php

namespace Gaufrette\Adapter\OpenStackCloudFiles;


use OpenCloud\OpenStack;

/**
 * Class OpenStackAuthenticationFactory
 * @package Gaufrette\Adapter\OpenStackCloudFiles
 * @author  Chris Warner <cdw.lighting@gmail.com>
 */
class OpenStackAuthenticationFactory extends BaseOpenStackAuthenticationFactory implements ConnectionFactoryInterface
{
    /**
     * @return OpenStack
     */
    public function create()
    {
        if (null === $this->authenciationService) {
            $this->authenciationService = new OpenStack($this->url, array ($this->username, $this->apikey));
            $this->authenciationService->authenticate();
            $this->authenciationService->setDefaults('cloudFiles', null, $this->region);
        } elseif ($this->authenciationService->hasExpired()) {
            $this->authenciationService->authenticate();
            $this->authenciationService->setDefaults('cloudFiles', null, $this->region);
        }

        return $this->authenciationService;
    }
}
