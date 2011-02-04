<?php

namespace Gaufrette\Filesystem\Adapter;

use Gaufrette\Filesystem\File;
use Gaufrette\Filesystem\Filesystem;
use Gaufrette\Filesystem\Adapter;

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

        $fs = $this->getFilesystem();

        foreach ($files as $file) {
            $fs->write($file, '', true);
        }

        $keys = $fs->keys('foo');
        $this->assertEquals(4, count($keys));
        foreach (array('foobar', 'foo/bar', 'foo/bar2', 'foo/foobar/bar') as $key) {
            $this->assertContains($key, $keys);
        }

        $keys = $fs->keys('foo/');
        $this->assertEquals(3, count($keys));
        foreach (array('foo/bar', 'foo/bar2', 'foo/foobar/bar') as $key) {
            $this->assertContains($key, $keys);
        }

        $keys = $fs->keys('bar/f');
        $this->assertEquals(array('bar/foo'), $keys);

        foreach ($files as $file) {
            $fs->delete($file);
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
