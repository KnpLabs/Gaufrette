<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\Local;

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

    /**
     * @test
     */
    public function shouldComputeKey()
    {
        $adapter = new Local($this->directory);

        $this->assertEquals('foobar', $adapter->computeKey($this->directory . '/foobar'));
        $this->assertEquals('foo/bar', $adapter->computeKey($this->directory . '/foo/bar'));
    }

    /**
     * @test
     */
    public function shouldComputeUnnormalizedKey()
    {
        $directory = str_replace('\\', '/', __DIR__) . '/filesystem/../filesystem';
        $adapter = new Local($directory);

        $this->assertEquals('foobar', $adapter->computeKey($directory . '/foobar'));
        $this->assertEquals('foo/bar', $adapter->computeKey($directory . '/foo/bar'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldCreateDirectory()
    {
        $dirname = sprintf(
            '%s/adapters/some',
            dirname(__DIR__)
        );

        @rmdir($dirname);
        $this->adapter->createDirectory($dirname);
        $this->assertTrue(is_dir($dirname));
        @rmdir($dirname);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWorkWithSyslink()
    {
        $dirname = sprintf(
            '%s/adapters/aaa',
            dirname(__DIR__)
        );
        $linkname = sprintf(
            '%s/adapters/../../../../link',
            dirname(__DIR__)
        );

        @mkdir($dirname);
        @unlink($linkname);
        symlink($dirname, $linkname);

        $adapter = new Local($linkname);
        $adapter->write('test.txt', 'abc 123');

        $this->assertSame('abc 123', $adapter->read('test.txt'));
        $adapter->delete('test.txt');
        @unlink($linkname);
        @rmdir($dirname);
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     */
    public function shouldListingOnlyGivenDirectory()
    {
        $dirname = sprintf(
            '%s/localDir',
            $this->directory
        );
        @mkdir($dirname);

        $adapter = new Local($this->directory);
        $adapter->write('/localDir/test.txt', 'some content');

        $dirs = $adapter->listDirectory('/localDir');

        $this->assertEmpty($dirs['dirs']);
        $this->assertCount(1, $dirs['keys']);
        $this->assertEquals('test.txt', $dirs['keys'][0]);

        $dirs = $adapter->listDirectory();

        $this->assertCount(1, $dirs['dirs']);
        $this->assertEquals('localDir', $dirs['dirs'][0]);
        $this->assertEmpty($dirs['keys']);

        @unlink($dirname.DIRECTORY_SEPARATOR.'test.txt');
        @rmdir($dirname);
    }
}
