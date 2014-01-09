<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Filesystem;
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

        $this->filesystem = new Filesystem(new Local($this->directory));
    }

    public function tearDown()
    {
        $this->filesystem = null;

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

        $this->filesystem = new Filesystem(new Local($linkname));
        $this->filesystem->write('test.txt', 'abc 123');

        $this->assertSame('abc 123', $this->filesystem->read('test.txt'));
        $this->filesystem->delete('test.txt');
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

        $this->filesystem = new Filesystem(new Local($this->directory));
        $this->filesystem->write('aaa.txt', 'some content');
        $this->filesystem->write('localDir/test.txt', 'some content');

        $dirs = $this->filesystem->listKeys('localDir/test');
        $this->assertEmpty($dirs['dirs']);
        $this->assertCount(1, $dirs['keys']);
        $this->assertEquals('localDir/test.txt', $dirs['keys'][0]);

        $dirs = $this->filesystem->listKeys();

        $this->assertCount(1, $dirs['dirs']);
        $this->assertEquals('localDir', $dirs['dirs'][0]);
        $this->assertCount(2, $dirs['keys']);
        $this->assertEquals('aaa.txt', $dirs['keys'][0]);
        $this->assertEquals('localDir/test.txt', $dirs['keys'][1]);

        @unlink($dirname.DIRECTORY_SEPARATOR.'test.txt');
        @unlink($this->directory.DIRECTORY_SEPARATOR.'aaa.txt');
        @rmdir($dirname);
    }

    /**
     * @test
     */
    public function shouldListKeys()
    {
        $this->filesystem->write('foo2/foobar/bar.txt', 'data');
        $this->filesystem->write('foo2/bar/buzz.txt', 'data');
        $this->filesystem->write('foo2barbuz.txt', 'data');
        $this->filesystem->write('foo3', 'data');

        $allKeys = $this->filesystem->listKeys('');
        //empty pattern results in ->keys call
        $this->assertEquals(
            array('foo2/bar/buzz.txt', 'foo2/foobar/bar.txt', 'foo2barbuz.txt', 'foo3'),
            $allKeys['keys']
        );

        //these values are canonicalized to avoid wrong order or keys issue

        $keys = $this->filesystem->listKeys('foo2');
        $this->assertEquals(
            array('foo2/bar/buzz.txt', 'foo2/foobar/bar.txt', 'foo2barbuz.txt'),
            $keys['keys'],
            '', 0, 10, true);

        $keys = $this->filesystem->listKeys('foo2/foob');
        $this->assertEquals(
            array('foo2/foobar/bar.txt'),
            $keys['keys'],
            '', 0, 10, true);

        $keys = $this->filesystem->listKeys('foo2/');
        $this->assertEquals(
            array('foo2/foobar/bar.txt', 'foo2/bar/buzz.txt'),
            $keys['keys'],
            '', 0, 10, true);

        $keys = $this->filesystem->listKeys('foo2');
        $this->assertEquals(
            array('foo2/bar/buzz.txt', 'foo2/foobar/bar.txt', 'foo2barbuz.txt'),
            $keys['keys'],
            '', 0, 10, true);

        $keys = $this->filesystem->listKeys('fooz');
        $this->assertEquals(
            array(),
            $keys['keys'],
            '', 0, 10, true);
    }
}
