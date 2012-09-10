<?php

namespace Gaufrette\Adapter;

use Gaufrette\Util\Checksum;

class InMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->adapter = new InMemory();
    }

    public function tearDown()
    {
        $this->adapter = null;
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldSetFiles()
    {
        $adapter = new InMemory(array(
            'foo' => 'Foo content',
            'bar' => 'Bar content'
        ));

        $this->assertFalse($adapter->exists('foobar'));
        $this->assertTrue($adapter->exists('foo'));
        $this->assertEquals('Foo content', $adapter->read('foo'));
        $this->assertTrue($adapter->exists('bar'));
        $this->assertEquals('Bar content', $adapter->read('bar'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldCalculateChecksumAutomaticallyFromContent()
    {
        $adapter = new InMemory(array(
            'foobar' => 'Some content'
        ));

        $this->assertEquals(Checksum::fromContent('Some content'), $adapter->checksum('foobar'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldManuallyDefineChecksum()
    {
        $adapter = new InMemory(array(
            'foobar' => array(
                'content'  => 'Some content',
                'checksum' => 'abcd'
            )
        ));

        $this->assertEquals('abcd', $adapter->checksum('foobar'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldUpdateChecksumOnWrite()
    {
        $adapter = new InMemory(array(
            'foobar' => array(
                'content'  => 'Some content',
                'checksum' => 'abcd'
            )
        ));

        $adapter->write('foobar', 'Changed content');

        $this->assertEquals(Checksum::fromContent('Changed content'), $adapter->checksum('foobar'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldAutomaticallySetLastModifiedTime()
    {
        $time = time();
        $adapter = new InMemory(array(
            'foobar' => 'Some content'
        ));

        $this->assertTrue(in_array($adapter->mtime('foobar'), array($time, $time + 1)));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldManuallyLastModifiedTime()
    {
        $adapter = new InMemory(array(
            'foobar' => array(
                'content' => 'Some content',
                'mtime'   => 123456789
            )
        ));

        $this->assertEquals(123456789, $adapter->mtime('foobar'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldUpdateLastModifiedTimeOnWrite()
    {
        $adapter = new InMemory(array(
            'foobar' => array(
                'content' => 'Some content',
                'mtime'   => 123456789
            )
        ));

        $time = time();
        $adapter->write('foobar', 'Changed content');

        $this->assertTrue(in_array($adapter->mtime('foobar'), array($time, $time + 1)));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldWriteContent()
    {
        $adapter = new InMemory(array());
        $adapter->write('foobar', 'Changed content');

        $this->assertSame('Changed content', $adapter->read('foobar'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldRenameFile()
    {
        $adapter = new InMemory(array('foo' => array('content' => 'Some content')));
        $adapter->rename('foo', 'bar');

        $this->assertFalse($adapter->exists('foo'));
        $this->assertTrue($adapter->exists('bar'));
        $this->assertSame('Some content', $adapter->read('bar'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     * @expectedException Gaufrette\Exception\FileNotFound
     * @expectedExceptionMessage The file "bar" was not found.
     */
    public function shouldFailWhenRenamedFileDoesNotExist()
    {
        $adapter = new InMemory(array('foo' => array('content' => 'Some content')));
        $adapter->rename('bar', 'bar2');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     * @expectedException Gaufrette\Exception\UnexpectedFile
     */
    public function shouldFailWhenTargetFileExists()
    {
        $adapter = new InMemory(array(
            'foo' => array('content' => 'Some content'),
            'bar' => array('content' => 'Some content1')
        ));
        $adapter->rename('foo', 'bar');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldGetFileKeys()
    {
        $adapter = new InMemory(array(
            'foo' => array('content' => 'Some content'),
            'bar' => array('content' => 'Some content1')
        ));

        $this->assertSame(array('foo', 'bar'), $adapter->keys());
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     */
    public function shouldDeleteFile()
    {
        $adapter = new InMemory(array(
            'foo' => array('content' => 'Some content'),
            'bar' => array('content' => 'Some content1')
        ));
        $adapter->delete('foo');

        $this->assertFalse($adapter->exists('foo'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\InMemory
     * @expectedException Gaufrette\Exception\FileNotFound
     * @expectedExceptionMessage The file "test" was not found.
     */
    public function shouldFailWhenTryDeleteFileWhichDoesNotExist()
    {
        $adapter = new InMemory(array(
            'foo' => array('content' => 'Some content'),
            'bar' => array('content' => 'Some content1')
        ));
        $adapter->delete('test');
    }
}
