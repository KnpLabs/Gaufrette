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
     * @dataProvider getDataToTestStreamOpenFileKey
     */
    public function testStreamOpenFileKey($domain, $uri, $key)
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
