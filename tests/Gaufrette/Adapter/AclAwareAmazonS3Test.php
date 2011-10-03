<?php

namespace Gaufrette\Adapter;

class AclAwareAmazonS3Test extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );
    }
}
