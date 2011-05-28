<?php

namespace Gaufrette\Adapter;

use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\Adapter;

abstract class AbstractFunctionalTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateAnDeleteFile()
    {
        $fs = $this->getFilesystem();

        $filename = uniqid('test_');

        $file = new File($filename, $fs);

        $this->assertFalse($file->exists());

        $file->setContent('Hello');

        $this->assertTrue($file->exists());

        $file->delete();

        $this->assertFalse($file->exists());
    }

    public function testKeys()
    {
        $files = array(
            'foobar',
            'foo/bar',
            'foo/bar2',
            'foo/foobar/bar',
            'bar/foo',
            'bar/bar'
        );

        $filesystem = $this->getFilesystem();

        foreach ($files as $file) {
            $filesystem->write($file, '', true);
        }

        $keys = $filesystem->keys();

        $this->assertEquals(count($files), count($keys));
        foreach ($files as $file) {
            $this->assertContains($file, $keys);
        }
    }

    protected function getFilesystem()
    {
        return new Filesystem($this->getAdapter());
    }

    /**
     * Returns a configured adapter instance
     *
     * @return Adapter
     */
    abstract protected function getAdapter();
}
