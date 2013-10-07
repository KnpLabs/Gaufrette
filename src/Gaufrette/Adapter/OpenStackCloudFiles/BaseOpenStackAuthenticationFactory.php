<?php

namespace Gaufrette\Adapter\OpenStackCloudFiles;


use OpenCloud\OpenStack;
use OpenCloud\Rackspace;

/**
 * Class BaseOpenStackAuthenticationFactory
 * @package Gaufrette\Adapter\OpenStackCloudFiles
 * @author  Chris Warner <cdw.lighting@gmail.com>
 */
abstract class BaseOpenStackAuthenticationFactory implements ConnectionFactoryInterface
{

    /**
     * @var null|OpenStack|Rackspace
     */
    protected $authenciationService = null;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var null|string
     */
    protected $tenant;
    /**
     * @var string
     */
    protected $apikey;
    /**
     * @var string
     */
    protected $region;

    /**
     * @param string      $url
     * @param string      $apikey
     * @param string      $username
     * @param string      $region
     * @param null|string $tenant
     */
    function __construct($url, $apikey, $username, $region, $tenant = null)
    {
        $this->apikey   = $apikey;
        $this->region   = $region;
        $this->tenant   = $tenant;
        $this->url      = $url;
        $this->username = $username;
    }
}
