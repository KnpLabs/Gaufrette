<?php

namespace Gaufrette\Adapter;

use Gaufrette\Checksum;

class InMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testSetFiles()
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

    public function testWriteAndRead()
    {
        $adapter = new InMemory();

        $this->assertFalse($adapter->exists('foobar'));

        $adapter->write('foobar', 'Some content');

        $this->assertTrue($adapter->exists('foobar'));
        $this->assertEquals('Some content', $adapter->read('foobar'));
    }

    public function testChecksumIsAutomaticallySetFromContent()
    {
        $adapter = new InMemory(array(
            'foobar' => 'Some content'
        ));

        $this->assertEquals(Checksum::fromContent('Some content'), $adapter->checksum('foobar'));
    }

    public function testManuallyDefineChecksum()
    {
        $adapter = new InMemory(array(
            'foobar' => array(
                'content'  => 'Some content',
                'checksum' => 'abcd'
            )
        ));

        $this->assertEquals('abcd', $adapter->checksum('foobar'));
    }

    public function testChecksumIsUpdatedOnWrite()
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

    public function testLastModifiedTimeIsAutomaticallySet()
    {
        $time = time();
        $adapter = new InMemory(array(
            'foobar' => 'Some content'
        ));

        $this->assertTrue(in_array($adapter->mtime('foobar'), array($time, $time + 1)));
    }

    public function testManuallyDefineLastModifiedTime()
    {
        $adapter = new InMemory(array(
            'foobar' => array(
                'content' => 'Some content',
                'mtime'   => 123456789
            )
        ));

        $this->assertEquals(123456789, $adapter->mtime('foobar'));
    }

    public function testLastModifiedTimeIsUpdatedOnWrite()
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
}
