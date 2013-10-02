<?php
/**
 * Created by PhpStorm.
 * User: cwarner
 * Date: 10/1/13
 * Time: 5:00 PM
 */

namespace Gaufrette\Adapter\OpenStackCloudFiles;


abstract class BaseOpenStackAuthenticationFactory implements ConnectionFactoryInterface {
    protected  $authenciationService = null;
    protected $url, $username, $tenant, $apikey, $region;

    /**
     * @param $url
     * @param $apikey
     * @param $username
     * @param $region
     * @param null $tenant
     */
    function __construct($url, $apikey, $username, $region, $tenant = null)
    {
        $this->apikey = $apikey;
        $this->region = $region;
        $this->tenant = $tenant;
        $this->url = $url;
        $this->username = $username;
    }

} 
