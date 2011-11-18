<?php

namespace Gaufrette\FileStream;

use Gaufrette\Filesystem;
use Gaufrette\Adapter;
use Gaufrette\StreamMode;

class InMemoryBufferTest extends \PHPUnit_Framework_TestCase
{
    public function testReadManyTimes()
    {
        $adapter = new Adapter\InMemory(array('THE_KEY'   => 'abcdefgh'));
        $stream  = new InMemoryBuffer($adapter, 'THE_KEY');
        $stream->open(new StreamMode('r'));

        $this->assertEquals('abc', $stream->read(3));
        $this->assertEquals('def', $stream->read(3));
        $this->assertEquals('gh', $stream->read(3));
        $this->assertEquals('', $stream->read(3));
    }

    public function testWriteFlushAndClose()
    {
        $adapter = new Adapter\InMemory();
        $stream = new InMemoryBuffer($adapter, 'THE_KEY');
        $stream->open(new StreamMode('w'));

        $this->assertTrue($adapter->exists('THE_KEY'));
        $this->assertEquals('', $adapter->read('THE_KEY'));

        $stream->write('foo');
        $this->assertEquals('', $adapter->read('THE_KEY'));

        $stream->flush();
        $this->assertEquals('foo', $adapter->read('THE_KEY'));

        $stream->write('bar');
        $stream->close();

        $this->assertEquals('foobar', $adapter->read('THE_KEY'));
    }
}
