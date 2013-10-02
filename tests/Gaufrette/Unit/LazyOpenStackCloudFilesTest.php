<?php
/**
 * Created by PhpStorm.
 * User: cwarner
 * Date: 10/2/13
 * Time: 2:14 PM
 */

namespace Gaufrette\Unit;


use Gaufrette\Adapter\LazyOpenStackCloudFiles;

class LazyOpenStackCloudFilesTest extends BaseOpenCloudTestCase {

    protected function buildOpenCloudClass($objectStore, $createContainer = false, $detectContentType = false)
    {
        $factory = $this->getMock('Gaufrette\Adapter\OpenStackCloudFiles\OpenStackAuthenticationFactory',
            array('create'), array(), '', false, false, false, false );

        $connection = $this->getMock('OpenCloud\OpenStack',
            array('objectStore'),array(), '', false, false, false, false
        );

        $connection->expects($this->once())
            ->method('objectStore')
            ->will($this->returnValue($objectStore));

        $factory->expects($this->once())
            ->method('create')
            ->will($this->returnValue($connection));

        $sut = new LazyOpenStackCloudFiles($factory, $createContainer, $detectContentType);

        return $sut;
    }

} 
