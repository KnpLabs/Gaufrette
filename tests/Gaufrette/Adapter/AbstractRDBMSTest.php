<?php

namespace Gaufrette\Adapter;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
abstract class AbstractRDBMSTest extends \PHPUnit_Framework_TestCase
{
    abstract protected function getAdapter();

    public function testWriteFile()
    {
        $adapter = $this->getAdapter();
        $this->assertEquals(5, $adapter->write('foo', 'hello'));
        $this->assertEquals(5, $adapter->write('foo', 'üüüüü'));
    }

    public function testExistFile()
    {
        $adapter = $this->getAdapter();
        $this->assertFalse($adapter->exists('foo'));
        $adapter->write('foo', 'bar');
        $this->assertTrue($adapter->exists('foo'));
    }

    public function testDelete()
    {
        $adapter = $this->getAdapter();
        $this->assertFalse($adapter->exists('foo'));
        $adapter->write('foo', 'bar');
        $this->assertTrue($adapter->exists('foo'));
        $this->assertTrue($adapter->delete('foo'));
        $this->assertFalse($adapter->exists('foo'));
    }

    public function testDeleteNotExistingFile()
    {
        $adapter = $this->getAdapter();
        $this->assertFalse($adapter->exists('foo'));
        $this->assertFalse($adapter->delete('foo'));
    }

    public function testMtime()
    {
        $adapter = $this->getAdapter();
        $time = time();
        $adapter->write('foo', '');
        $mtime = $adapter->mtime('foo');

        $this->assertInternalType('integer', $mtime);
        $this->assertGreaterThanOrEqual($time, $mtime);
    }

    public function testMtimeFromNotExistingFile()
    {
        $adapter = $this->getAdapter();
        $this->assertFalse($adapter->exists('foo'));
        $this->assertFalse($adapter->mtime('foo'));
    }

    public function testRename()
    {
        $adapter = $this->getAdapter();

        $adapter->write('foo', '');
        $this->assertTrue($adapter->exists('foo'));
        $this->assertFalse($adapter->exists('bar'));

        $this->assertTrue($adapter->rename('foo', 'bar'));
        $this->assertFalse($adapter->exists('foo'));
        $this->assertTrue($adapter->exists('bar'));
    }

    public function testRenameNotExistingFile()
    {
        $adapter = $this->getAdapter();

        $this->assertFalse($adapter->exists('foo'));
        $this->assertFalse($adapter->rename('foo', 'bar'));
    }

    /**
     * @dataProvider getChecksumData
     */
    public function testChecksum($content, $checksum)
    {
        $key = 'foo';
        $adapter = $this->getAdapter();

        $adapter->write($key, $content);
        $this->assertEquals($checksum, $adapter->checksum($key));
    }

    public function testKeys()
    {
        $adapter = $this->getAdapter();
        
        $files = array(
            'foo' => 'bar',
            'memo.txt' => 'Lorem',
            'memo.html' => '<!DOCTYPE><html></html>',
        );

        foreach ($files as $key => $content) {
            $adapter->write($key, $content);
        }

        $keys = $adapter->keys();

        $this->assertEquals(count($files), count($keys));
        $this->assertEquals(array_keys($files), $keys);
    }

    public function testSupportMetadata()
    {
        $adapter = $this->getAdapter();
        $this->assertTrue($adapter->supportsMetadata());
    }

    public function getChecksumData()
    {
        return array(
            array('', 'd41d8cd98f00b204e9800998ecf8427e'),
            array('foo', 'acbd18db4cc2f85cedef654fccc4a4d8'),
            array(1, 'c4ca4238a0b923820dcc509a6f75849b')
        );
    }
}
