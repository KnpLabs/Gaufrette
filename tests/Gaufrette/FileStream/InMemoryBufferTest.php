<?php

namespace Gaufrette\FileStream;

use Gaufrette\Filesystem;
use Gaufrette\Adapter;
use Gaufrette\StreamMode;

class InMemoryBufferTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers Gaufrette\FileStream\InMemoryBuffer
     * @covers Gaufrette\FileStream
     */
    public function shouldBeAbleToReadManyTimes()
    {
        $adapter = new Adapter\InMemory(array('THE_KEY'   => 'abcdefgh'));
        $stream  = new InMemoryBuffer($adapter, 'THE_KEY');
        $stream->open(new StreamMode('r'));

        $this->assertEquals('abc', $stream->read(3));
        $this->assertEquals('def', $stream->read(3));
        $this->assertEquals('gh', $stream->read(3));
        $this->assertEquals('', $stream->read(3));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\InMemoryBuffer
     * @covers Gaufrette\FileStream
     */
    public function shouldWriteAndFlushContentInStream()
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

    /**
     * @test
     * @covers Gaufrette\FileStream\InMemoryBuffer
     * @covers Gaufrette\FileStream
     */
    public function shouldStatFileInStream()
    {
        $statInfo = array(
            'dev'   => 1,
            'ino'   => 0,
            'mode'  => 0777,
            'nlink' => 0,
            'uid'   => 0,
            'gid'   => 0,
            'rdev'  => 0,
            'size'  => strlen('some content'),
            'blksize' => -1,
            'blocks'  => -1,
        );

        $adapter = new Adapter\InMemory(array('THE_KEY' => 'some content'));
        $stream = new InMemoryBuffer($adapter, 'THE_KEY');
        $stream->open(new StreamMode('r+'));

        $returnedStatInfo = $stream->stat();

        $this->assertSame($statInfo['dev'], $returnedStatInfo['dev']);
        $this->assertSame($statInfo['size'], $returnedStatInfo['size']);
        $this->assertSame($statInfo['dev'], $returnedStatInfo[0]);
        $this->assertSame($statInfo['size'], $returnedStatInfo[7]);
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\InMemoryBuffer
     * @covers Gaufrette\FileStream
     */
    public function shouldBeAbleToUnlinkFile()
    {
        $adapter = new Adapter\InMemory(array('test.txt' => 'some content'));
        $stream = new InMemoryBuffer($adapter, 'test.txt');
        $stream->open(new StreamMode('w+'));

        $this->assertTrue($stream->unlink());
        $this->assertFalse($adapter->exists('test.txt'));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\InMemoryBuffer
     * @covers Gaufrette\FileStream
     */
    public function shouldNotUnlinkFileWhenNotOpened()
    {
        $adapter = new Adapter\InMemory(array('test.txt' => 'some content'));
        $stream = new InMemoryBuffer($adapter, 'test.txt');

        $this->assertFalse($stream->unlink());
        $this->assertTrue($adapter->exists('test.txt'));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\InMemoryBuffer
     * @covers Gaufrette\FileStream
     */
    public function shouldNotUnlinkWhenDoNotImpliesContentDeletion()
    {
        $adapter = new Adapter\InMemory(array('test.txt' => 'some content'));
        $stream = new InMemoryBuffer($adapter, 'test.txt');
        $stream->open(new StreamMode('r'));

        $this->assertFalse($stream->unlink());
        $this->assertTrue($adapter->exists('test.txt'));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\InMemoryBuffer
     * @covers Gaufrette\FileStream
     */
    public function shouldNotBeAbleToCastToResource()
    {
        $adapter = new Adapter\InMemory();
        $stream = new InMemoryBuffer($adapter, 'THE_KEY');

        $this->assertFalse($stream->cast(STREAM_CAST_FOR_SELECT));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\InMemoryBuffer
     * @covers Gaufrette\FileStream
     */
    public function shouldDoNotFlushSuccessfullyWhenWriteThrowsErrors()
    {
        $adapter = $this->getMock('Gaufrette\Adapter');
        $adapter->expects($this->any())
            ->method('write')
            ->will($this->throwException(new \Exception));
        $stream = new InMemoryBuffer($adapter, 'test.txt');

        $this->assertFalse($stream->flush());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\InMemoryBuffer
     * @covers Gaufrette\FileStream
     */
    public function shouldSetAndGetPositions()
    {
        $adapter = new Adapter\InMemory(array('key' => 'content'));
        $stream = new InMemoryBuffer($adapter, 'key');

        $stream->seek(2, SEEK_SET);
        $this->assertEquals(2, $stream->tell());
        $stream->seek(1, SEEK_CUR);
        $this->assertEquals(3, $stream->tell());
    }
}
