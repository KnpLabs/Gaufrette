<?php

namespace Gaufrette;

class StreamWrapperTest extends \PHPUnit_Framework_TestCase
{
    private $filesystemMap;

    public function setUp()
    {
        $this->filesystemMap = new FilesystemMap();

        StreamWrapper::setFilesystemMap($this->filesystemMap);
    }

    /**
     * @test
     * @dataProvider getDataToTestStreamOpenFileKey
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldOpenStreamForFileKey($domain, $uri, $key)
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->with($this->equalTo($key))
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set($domain, $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open($uri, 'r');
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     * @expectedException \InvalidArgumentException
     */
    public function shouldFailWhenTryOpenStreamWithoutDomain()
    {
        $wrapper = new StreamWrapper();
        $wrapper->stream_open('', 'r');
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldReadFromStream()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('read')
            ->with($this->equalTo(30))
            ->will($this->returnValue('value from stream'))
        ;

        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open('gaufrette://foo/test', 'r');
        $this->assertSame('value from stream', $wrapper->stream_read(30));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotFailWhenReadingStreamWhichWasNotOpen()
    {
        $stream = $this->getFileStreamMock();
        $filesystem = $this->getFilesystemMock();

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $this->assertFalse($wrapper->stream_read(30));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldWriteToStream()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Content to write'))
            ->will($this->returnValue(12))
        ;

        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open('gaufrette://foo/test', 'w');
        $this->assertSame(12, $wrapper->stream_write('Content to write'));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotFailWhenWriteToStreamWhichWasNotOpen()
    {
        $wrapper = new StreamWrapper();
        $this->assertSame(0, $wrapper->stream_write('Content to write'));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldCloseStream()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('close')
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open('gaufrette://foo/test', 'w');
        $wrapper->stream_close();
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotFailWhenCloseStreamWhichWasNotOpen()
    {
        $wrapper = new StreamWrapper();
        $wrapper->stream_close();
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldFlushStream()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('flush')
            ->will($this->returnValue(true))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open('gaufrette://foo/test', 'w');
        $this->assertTrue($wrapper->stream_flush());
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotFailWhenFlushStreamWhichWasNotOpen()
    {
        $wrapper = new StreamWrapper();
        $this->assertFalse($wrapper->stream_flush());
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldSeekPositionInStream()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('seek')
            ->with($this->equalTo(12), $this->equalTo(SEEK_CUR))
            ->will($this->returnValue(true))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open('gaufrette://foo/test', 'r');
        $this->assertTrue($wrapper->stream_seek(12, SEEK_CUR));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotFailWhenSeekPositionInStreamWhichWasNotOpen()
    {
        $wrapper = new StreamWrapper();
        $this->assertFalse($wrapper->stream_seek(12, SEEK_CUR));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldGetCurrentPositionFromStream()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('tell')
            ->will($this->returnValue(44))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open('gaufrette://foo/test', 'r');
        $this->assertEquals(44, $wrapper->stream_tell());
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotFailWhenGetCurrentPositionFromStreamWhichWasNotOpen()
    {
        $wrapper = new StreamWrapper();
        $this->assertFalse($wrapper->stream_tell());
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldCheckEOFInStream()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('eof')
            ->will($this->returnValue(false))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open('gaufrette://foo/test', 'r');
        $this->assertFalse($wrapper->stream_eof());
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotFailWhenCheckEOFInStreamWhichWasNotOpen()
    {
        $wrapper = new StreamWrapper();
        $this->assertTrue($wrapper->stream_eof());
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldGetStatInfoFromStream()
    {
        $statInfo = array(
            'dev'   => 1,
            'ino'   => 12,
            'mode'  => 0777,
            'nlink' => 0,
            'uid'   => 123,
            'gid'   => 1,
            'rdev'  => 0,
            'size'  => 666,
            'atime' => 1348030800,
            'mtime' => 1348030800,
            'ctime' => 1348030800,
            'blksize' => 5,
            'blocks'  => 1,
        );

        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('stat')
            ->will($this->returnValue($statInfo))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open('gaufrette://foo/test', 'r');
        $this->assertSame($statInfo, $wrapper->stream_stat());
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldUnlinkFileInStream()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('unlink')
            ->will($this->returnValue(true))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $this->assertTrue($wrapper->unlink('gaufrette://foo/test'));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotUnlinkTheFileWhenCannotOpenStream()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
            ->will($this->throwException(new \RuntimeException))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $this->assertFalse($wrapper->unlink('gaufrette://foo/test'));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotFailWhenGetStatFromStreamWhichWasNotOpen()
    {
        $wrapper = new StreamWrapper();
        $this->assertFalse($wrapper->stream_stat());
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldBeCastedUsingStreamCast()
    {

        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
        ;
        $stream
            ->expects($this->once())
            ->method('cast')
            ->with($this->equalTo(STREAM_CAST_FOR_SELECT))
            ->will($this->returnValue('resource'))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $wrapper->stream_open('gaufrette://foo/test', 'r');
        $this->assertSame('resource', $wrapper->stream_cast(STREAM_CAST_FOR_SELECT));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldNotFailWhenTryCastStreamWhichWasNotOpen()
    {
        $wrapper = new StreamWrapper();
        $this->assertFalse($wrapper->stream_cast(STREAM_CAST_FOR_SELECT));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldGetUrlStatInfoFromStream()
    {
        $statInfo = array(
            'dev'   => 1,
            'ino'   => 12,
            'mode'  => 0777,
            'nlink' => 0,
            'uid'   => 123,
            'gid'   => 1,
            'rdev'  => 0,
            'size'  => 666,
            'atime' => 1348030800,
            'mtime' => 1348030800,
            'ctime' => 1348030800,
            'blksize' => 5,
            'blocks'  => 1,
        );

        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('stat')
            ->will($this->returnValue($statInfo))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $this->assertSame($statInfo, $wrapper->url_stat('gaufrette://foo/test', STREAM_URL_STAT_LINK));
    }

    /**
     * @test
     * @covers Gaufrette\StreamWrapper
     */
    public function shouldWorkWhenFileCannotBeOpened()
    {
        $stream = $this->getFileStreamMock();
        $stream
            ->expects($this->once())
            ->method('open')
            ->will($this->throwException(new \RuntimeException))
        ;
        $filesystem = $this->getFilesystemMock();
        $filesystem
            ->expects($this->once())
            ->method('createFileStream')
            ->will($this->returnValue($stream))
        ;

        $this->filesystemMap->set('foo', $filesystem);

        $wrapper = new StreamWrapper();
        $this->assertFalse($wrapper->url_stat('gaufrette://foo/test', STREAM_URL_STAT_LINK));
    }

    public function getDataToTestStreamOpenFileKey()
    {
        return array(
            array(
                'foo',
                'gaufrette://foo/the/file/key',
                'the/file/key'
            ),
            array(
                'foo',
                'gaufrette://foo//the/file/key',
                '/the/file/key'
            ),
            array(
                'foo',
                'gaufrette://foo/the/file/key?hello=world#yeah',
                'the/file/key?hello=world#yeah'
            ),
        );
    }

    private function getFileStreamMock()
    {
        return $this->getMock('Gaufrette\FileStream');
    }

    private function getFilesystemMock()
    {
        return $this->getMock('Gaufrette\Filesystem', array(), array(), '', false);
    }
}
