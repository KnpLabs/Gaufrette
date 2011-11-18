<?php

namespace Gaufrette\Adapter;

class AmazonS3Test extends \PHPUnit_Framework_TestCase
{
    protected $service;

    public function setUp()
    {
        if (!class_exists('\AmazonS3')) {
            $this->markTestSkipped('The zend amazon s3 service class is not available.');
        }

        $this->service = $this->getMock('\AmazonS3', array(), array(), '', false);
    }

    public function tearDown()
    {
        $this->service = null;
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

    public function testPrependBaseDirectory()
    {
        $adapter = new AmazonS3($this->service, 'foobucket');
        $adapter->setDirectory('subdirectory');

        $this->assertEquals('subdirectory/foobar', $adapter->prependBaseDirectory('foobar'));
    }

    public function testPrependBaseDirectoryWithoutDirectory()
    {
        $adapter = new AmazonS3($this->service, 'foobucket');

        $this->assertEquals('foobar', $adapter->prependBaseDirectory('foobar'));
    }

    public function testComputePathWithBaseDirectory()
    {
        $adapter = new AmazonS3($this->service, 'foobucket');
        $adapter->setDirectory('subdirectory');

        $this->assertEquals('foobucket/subdirectory/foobar', $adapter->computePath('foobar'));
    }

    public function testComputeKeyWithBaseDirectory()
    {
        $adapter = new AmazonS3($this->service, 'foobucket');
        $adapter->setDirectory('subdirectory');

        $this->assertEquals('foobar', $adapter->computeKey('foobucket/subdirectory/foobar'));
    }

    public function testComputeKeyThrowsAnExceptionWhenTheSpecifiedPathIsNotValid()
    {
        $adapter = new AmazonS3($this->service, 'foobucket');

        $this->setExpectedException('InvalidArgumentException');

        $adapter->computeKey('barbucket/foobar');
    }
}
