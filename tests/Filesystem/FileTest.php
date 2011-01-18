<?php

namespace Gaufrette\Filesystem;

class FileTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContentReadsTheContentFromTheFilesystem()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
           ->method('read')
           ->with($this->equalTo('myFile'));

        $file = new File('myFile', $fs);
        $file->read('myFile');
    }

    public function testGetContentThrowsAnExceptionIfNoFilesystemIsConfigured()
    {
        $file = new File('myFile');

        $this->setExcpectedException('LogicException');

        $file->getContent();
    }

    public function testSetContentWritesTheContenIntoTheFilesystem()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
           ->method('write')
           ->with($this->equalTo('myFile'), $this->equalTo('some content'));

        $file = new File('myFile', $fs);
        $file->setContent('some content');
    }

    public function testSetContentThrowsAnExceptionIfNoFilesystemIsConfigured()
    {
        $file = new File('myFile');

        $this->setExcpectedException('LogicException');

        $file->setContent('some content');
    }

    protected function getFilesystemMock()
    {
        return $this->getMock('Gaufrette\Filesystem\Filesystem', array(), array(), '', false);
    }
}
