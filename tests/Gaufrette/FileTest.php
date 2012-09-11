<?php

namespace Gaufrette;

class FileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldReadsTheFileContentFromFilesystem()
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

    /**
     * @test
     */
    public function shouldFailWhenTryReadFileWhichDoesNotExist()
    {
        $this->setExpectedException('LogicException');

        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
            ->method('has')
            ->with($this->equalTo('myFile'))
            ->will($this->returnValue(false));

        $file = new File('myFile', $fs);
        $file->getContent();
    }

    /**
     * @test
     */
    public function shouldWriteFileContentToFilesystem()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
           ->method('write')
           ->with($this->equalTo('myFile'), $this->equalTo('some content'));

        $file = new File('myFile', $fs);
        $file->setContent('some content');
    }

    protected function getFilesystemMock()
    {
        return $this->getMock('Gaufrette\Filesystem', array(), array(), '', false);
    }
}
