<?php
/**
 * Created by PhpStorm.
 * User: cwarner
 * Date: 10/1/13
 * Time: 5:02 PM
 */

namespace Gaufrette\Adapter\OpenStackCloudFiles;


use OpenCloud\OpenStack;

class OpenStackAuthenticationFactory extends BaseOpenStackAuthenticationFactory implements ConnectionFactoryInterface {
    public function create()
    {
        if(null === $this->authenciationService)
        {
            $this->authenciationService = new OpenStack($this->url, array($this->username, $this->apikey));
            $this->authenciationService->authenticate();
            $this->authenciationService->setDefaults('cloudFiles', null, $this->region);
        } elseif($this->authenciationService->hasExpired()) {
            $this->authenciationService->authenticate();
            $this->authenciationService->setDefaults('cloudFiles', null, $this->region);
        }
        return $this->authenciationService;

    }

} 
