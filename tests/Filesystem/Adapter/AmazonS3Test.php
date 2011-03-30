<?php

namespace Gaufrette\Filesystem\Adapter;

class AmazonS3Test extends \PHPUnit_Framework_TestCase
{
    protected $service;

    public function setUp()
    {
        if (!class_exists('Zend\Service\Amazon\S3\S3')) {
            $this->markTestSkipped('The zend amazon s3 service class is not available.');
        }

        $this->service = $this->getMock('Zend\Service\Amazon\S3\S3', array(), array(), '', false);
//        $this->service->expects($this->any())
//            ->method('isBucketAvailable')
//            ->with($this->equalTo('foobucket'))
//            ->will($this->returnValue(true));
    }

    public function testComputePath()
    {
        $adapter = new AmazonS3($this->service, 'foobucket');

        $this->assertEquals('foobucket/foobar', $adapter->computePath('foobar'));
    }

    public function testComputeKey()
    {
        $adapter = new AmazonS3($this->service, 'foobucket');

        $this->assertEquals('foobar', $adapter->computeKey('foobucket/foobar'));
    }

    public function testComputeKeyThrowsAnExceptionWhenTheSpecifiedPathIsNotValid()
    {
        $adapter = new AmazonS3($this->service, 'foobucket');

        $this->setExpectedException('InvalidArgumentException');

        $adapter->computeKey('barbucket/foobar');
    }
}
