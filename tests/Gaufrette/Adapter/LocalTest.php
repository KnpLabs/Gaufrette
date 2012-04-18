<?php

namespace Gaufrette\Adapter;

class LocalTest extends FunctionalTestCase
{
    private $directory;

    public function setUp()
    {
        $this->directory = sprintf('%s/filesystem', str_replace('\\', '/', __DIR__));

        if (!file_exists($this->directory)) {
            mkdir($this->directory);
        }

        $this->adapter = new Local($this->directory);
    }

    public function tearDown()
    {
        $this->adapter = null;

        if (file_exists($this->directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->directory,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
                )
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir(strval($item));
                } else {
                    unlink(strval($item));
                }
            }
        }
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
