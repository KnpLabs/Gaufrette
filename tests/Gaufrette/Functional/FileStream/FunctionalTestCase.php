<?php

namespace Gaufrette\Functional\FileStream;

use Gaufrette\StreamWrapper;

class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $filesystem;

    /**
     * @test
     */
    public function shouldCheckIsFile()
    {
        $this->filesystem->write('test.txt', 'some content');
        $this->assertTrue(is_file('gaufrette://filestream/test.txt'));

        $this->filesystem->delete('test.txt');
        $this->assertFalse(is_file('gaufrette://filestream/test.txt'));
    }

    /**
     * @test
     */
    public function shouldCheckFileExists()
    {
        $this->filesystem->write('test.txt', 'some content');
        $this->assertTrue(file_exists('gaufrette://filestream/test.txt'));

        $this->filesystem->delete('test.txt');
        $this->assertFalse(file_exists('gaufrette://filestream/test.txt'));
    }

    /**
     * @test
     */
    public function shouldWriteAndReadFile()
    {
        file_put_contents('gaufrette://filestream/test.txt', 'test content');

        $this->assertEquals('test content', file_get_contents('gaufrette://filestream/test.txt'));
        $this->filesystem->delete('test.txt');
    }

    /**
     * @test
     */
    public function shouldUnlinkFile()
    {
        $this->filesystem->write('test.txt', 'some content');
        unlink('gaufrette://filestream/test.txt');

        $this->assertFalse($this->filesystem->has('test.txt'));
    }

    protected function registerLocalFilesystemInStream()
    {
        $filesystemMap = StreamWrapper::getFilesystemMap();
        $filesystemMap->set('filestream', $this->filesystem);
        StreamWrapper::register();
    }
}
