<?php

namespace Gaufrette\Filesystem\Adapter;

class LocalTest extends \PHPUnit_Framework_TestCase
{
    public function testComputePath()
    {
        $directory = str_replace('\\', '/', __DIR__) . '/filesystem';
        $adapter = new Local($directory);

        $this->assertEquals($directory . '/foobar', $adapter->computePath('foobar'));
        $this->assertEquals($directory . '/bar', $adapter->computePath('foo/../bar'));
        $this->assertEquals($directory . '/foo', $adapter->computePath('../filesystem/foo'));

        $this->setExpectedException('OutOfBoundsException');

        $adapter->computePath('../foobar');
    }

    public function testComputeKey()
    {
        $directory = str_replace('\\', '/', __DIR__) . '/filesystem';
        $adapter = new Local($directory);

        $this->assertEquals('foobar', $adapter->computeKey($directory . '/foobar'));
        $this->assertEquals('foo/bar', $adapter->computeKey($directory . '/foo/bar'));
    }

    public function testComputeKeyUnnormalized()
    {
        $directory = str_replace('\\', '/', __DIR__) . '/filesystem/../filesystem';
        $adapter = new Local($directory);

        $this->assertEquals('foobar', $adapter->computeKey($directory . '/foobar'));
        $this->assertEquals('foo/bar', $adapter->computeKey($directory . '/foo/bar'));
    }
}
