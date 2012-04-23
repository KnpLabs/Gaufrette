<?php

namespace Gaufrette\Adapter;

abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    public function getAdapterName()
    {
        if (!preg_match('/\\\\(\w+)Test$/', get_class($this), $matches)) {
            throw new \RuntimeException(sprintf(
                'Unable to guess adapter name from class "%s", '.
                'please override the ->getAdapterName() method.',
                get_class($this)
            ));
        }

        return $matches[1];
    }

    public function setUp()
    {
        $basename = $this->getAdapterName();
        $filename = sprintf(
            '%s/adapters/%s.php',
            dirname(dirname(__DIR__)),
            $basename
        );

        if (!file_exists($filename)) {
            return $this->markTestSkipped(<<<EOF
To run the {$basename} adapter tests, you must:

 1. Copy the file "{$filename}.dist" as "{$filename}"
 2. Modify the copied file to fit your environment
EOF
            );
        }

        $this->adapter = include $filename;
    }

    public function tearDown()
    {
        if (null === $this->adapter) {
            return;
        }

        $this->adapter = null;
    }

    public function testWriteAndRead()
    {
        $this->assertEquals(12, $this->adapter->write('foo', 'Some content'));

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
