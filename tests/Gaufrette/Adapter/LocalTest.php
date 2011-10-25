<?php

namespace Gaufrette\Adapter;

class LocalTest extends \PHPUnit_Framework_TestCase
{
    protected $directory;

    public function setUp()
    {
        $this->directory = str_replace('\\', '/', __DIR__) . '/filesystem';
        if (!file_exists($this->directory) && false === @mkdir($this->directory)) {
            $this->markTestSkipped('Test directory doesn\'t exists and cannot be created.');
        }
    }

    public function tearDown()
    {
        @unlink($this->directory);
    }

    public function testComputePath()
    {
        $adapter = new Local($this->directory);

        $this->assertEquals($this->directory . '/foobar', $adapter->computePath('foobar'));
        $this->assertEquals($this->directory . '/bar', $adapter->computePath('foo/../bar'));
        $this->assertEquals($this->directory . '/foo', $adapter->computePath('../filesystem/foo'));

        $this->setExpectedException('OutOfBoundsException');

        $adapter->computePath('../foobar');
    }

    public function testComputeKey()
    {
        $adapter = new Local($this->directory);

        $this->assertEquals('foobar', $adapter->computeKey($this->directory . '/foobar'));
        $this->assertEquals('foo/bar', $adapter->computeKey($this->directory . '/foo/bar'));
    }

    public function testComputeKeyUnnormalized()
    {
        $directory = str_replace('\\', '/', __DIR__) . '/filesystem/../filesystem';
        $adapter = new Local($directory);

        $this->assertEquals('foobar', $adapter->computeKey($directory . '/foobar'));
        $this->assertEquals('foo/bar', $adapter->computeKey($directory . '/foo/bar'));
    }
}
