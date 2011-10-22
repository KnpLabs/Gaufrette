<?php

namespace Gaufrette\Adapter;

class AclAwareAmazonS3Test extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('\AmazonS3')) {
            $this->markTestSkipped('The zend amazon s3 service class is not available.');
        }
    }

    public function testConstruct()
    {
        new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );
    }
}
