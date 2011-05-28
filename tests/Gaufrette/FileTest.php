<?php

namespace Gaufrette;

class FileTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContentReadsTheContentFromTheFilesystem()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
           ->method('has')
           ->with($this->equalTo('myFile'))
           ->will($this->returnValue(true));
        $fs->expects($this->once())
           ->method('read')
           ->with($this->equalTo('myFile'));

        $file = new File('myFile', $fs);
        $file->getContent('myFile');
    }

    public function testGetContentThrowsAnExceptionIfNoFilesystemIsConfigured()
    {
        $file = new File('myFile');

        $this->setExpectedException('LogicException');

        $file->getContent();
    }

    public function testGetContentThrowsAnExceptionIfTheFileDoesNotExistsInTheFilesystem()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
            ->method('has')
            ->with($this->equalTo('myFile'))
            ->will($this->returnValue(false));

        $file = new File('myFile', $fs);

        $this->setExpectedException('LogicException');

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

        $this->setExpectedException('LogicException');

        $file->setContent('some content');
    }

    protected function getFilesystemMock()
    {
        return $this->getMock('Gaufrette\Filesystem', array(), array(), '', false);
    }
}
