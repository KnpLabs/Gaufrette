<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;

class LocalTest extends FunctionalTestCase
{
    private $directory;

    public function setUp()
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('gaufrette-tests');

        if (!file_exists($this->directory)) {
            mkdir($this->directory);
        }

        $this->filesystem = new Filesystem(new Local($this->directory));
    }

    public function tearDown()
    {
        $adapter = $this->filesystem->getAdapter();

        $keys = $this->filesystem->keys();

        // sort keys by length DESC
        usort($keys, static function ($a, $b) {
            if ($a === $b) {
                return 0;
            }

            return strlen($a) > strlen($b) ? -1 : 1;
        });

        foreach ($keys as $key) {
            $adapter->delete($key);
        }

        $this->filesystem = null;

        rmdir($this->directory);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWorkWithSyslink()
    {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            $this->markTestSkipped('Symlinks are not supported on Windows.');
        }

        $dirname = sprintf('%s/dirname', $this->directory);
        $linkname = sprintf('%s/link', $this->directory);

        @mkdir($dirname);
        @unlink($linkname);
        symlink($dirname, $linkname);

        $fs = new Filesystem(new Local($linkname));
        $fs->write('test.txt', 'abc 123');

        $this->assertSame('abc 123', $fs->read('test.txt'));
        $fs->delete('test.txt');

        @unlink($linkname);
        @rmdir($dirname);
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @group functional
     */
    public function shouldListingOnlyGivenDirectory()
    {
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
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @group functional
     */
    public function shouldListingAllKeys()
    {
        $this->filesystem->write('aaa.txt', 'some content');
        $this->filesystem->write('localDir/dir1/dir2/dir3/test.txt', 'some content');

        $keys = $this->filesystem->keys();
        $dirs = $this->filesystem->listKeys();

        $this->assertCount(6, $keys);
        $this->assertCount(4, $dirs['dirs']);
        $this->assertEquals('localDir/dir1/dir2/dir3/test.txt', $dirs['keys'][1]);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldBeAbleToClearCache()
    {
        $this->filesystem->get('test.txt', true);
        $this->filesystem->write('test.txt', '123', true);

        $this->filesystem->get('test2.txt', true);
        $this->filesystem->write('test2.txt', '123', true);

        $fsReflection = new \ReflectionClass($this->filesystem);

        $fsIsFileInRegister = $fsReflection->getMethod('isFileInRegister');
        $fsIsFileInRegister->setAccessible(true);

        $this->assertTrue($fsIsFileInRegister->invoke($this->filesystem, 'test.txt'));
        $this->filesystem->removeFromRegister('test.txt');
        $this->assertFalse($fsIsFileInRegister->invoke($this->filesystem, 'test.txt'));

        $this->filesystem->clearFileRegister();
        $fsRegister = $fsReflection->getProperty('fileRegister');
        $fsRegister->setAccessible(true);
        $this->assertCount(0, $fsRegister->getValue($this->filesystem));
    }

    /**
     * @test
     * @group functional
     * @expectedException Gaufrette\Exception\StorageFailure
     */
    public function shouldThrowWhenTryingToReadADirectory()
    {
        $this->filesystem->getAdapter()->read('/');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldDeleteDirectory()
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'delete-me.d';
        mkdir($path);

        $this->assertTrue(is_dir($path));

        $this->filesystem->getAdapter()->delete('delete-me.d');

        $this->assertFalse(is_dir($path));
    }

    /**
     * @test
     * @group functional
     * @expectedException Gaufrette\Exception\StorageFailure
     */
    public function shouldNotDeleteTheAdapterRootDirectory()
    {
        $this->filesystem->getAdapter()->delete('/');
    }
}
