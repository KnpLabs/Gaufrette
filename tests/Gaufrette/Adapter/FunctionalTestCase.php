<?php

namespace Gaufrette\Adapter;

abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    public function testWriteAndRead()
    {
        $this->adapter->write('foo', 'Some content');

        $this->assertEquals('Some content', $this->adapter->read('foo'));
    }

    public function testExists()
    {
        $this->assertFalse($this->adapter->exists('foo'));

        $this->adapter->write('foo', 'Some content');

        $this->assertTrue($this->adapter->exists('foo'));
    }

    public function testReadNonExistingFile()
    {
        $this->setExpectedException('Gaufrette\Exception\FileNotFound');

        $this->adapter->read('foo');
    }

    public function testChecksum()
    {
        $this->adapter->write('foo', 'Some content');

        $this->assertEquals(md5('Some content'), $this->adapter->checksum('foo'));
    }

    public function testChecksumNonExistingFile()
    {
        $this->setExpectedException('Gaufrette\Exception\FileNotFound');

        $this->adapter->checksum('foo');
    }

    public function testMtime()
    {
        $this->adapter->write('foo', 'Some content');

        $this->assertEquals(time(), $this->adapter->mtime('foo'), null, 1);
    }

    public function testMtimeNonExistingFile()
    {
        $this->setExpectedException('Gaufrette\Exception\FileNotFound');

        $this->adapter->mtime('foo');
    }

    public function testRename()
    {
        $this->adapter->write('foo', 'Some content');
    }

    public function testRenameNonExistingFile()
    {
        $this->setExpectedException('Gaufrette\Exception\FileNotFound');

        $this->adapter->rename('foo', 'bar');
    }

    public function testDelete()
    {
        $this->adapter->write('foo', 'Some content');

        $this->assertTrue($this->adapter->exists('foo'));

        $this->adapter->delete('foo');

        $this->assertFalse($this->adapter->exists('foo'));
    }

    public function testDeleteNonExistingFile()
    {
        $this->setExpectedException('Gaufrette\Exception\FileNotFound');

        $this->adapter->delete('foo');
    }

    public function testKeys()
    {
        $this->assertEquals(array(), $this->adapter->keys());

        $this->adapter->write('foo', 'Some content');
        $this->adapter->write('bar', 'Some content');
        $this->adapter->write('baz', 'Some content');

        $actualKeys = $this->adapter->keys();

        $this->assertEquals(3, count($actualKeys));
        foreach (array('foo', 'bar', 'baz') as $key) {
            $this->assertContains($key, $actualKeys);
        }
    }
}
