<?php

namespace Gaufrette\Adapter\OpenStackCloudFiles;


/**
 * Interface ConnectionFactoryInterface
 * @package Gaufrette\Adapter\OpenStackCloudFiles
 * @author  Chris Warner <cdw.lighting@gmail.com>
 * @deprecated in favor of ObjectStoreFactory
 */
interface ConnectionFactoryInterface
{
    public function create();
}
