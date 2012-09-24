<?php

namespace Gaufrette;

class FileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers Gaufrette\File
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
     * @covers Gaufrette\File
     */
    public function shouldGetFileKey()
    {
        $file = new File('myFile', $this->getFilesystemMock());
        $this->assertEquals('myFile', $file->getKey());
    }

    /**
     * @test
     * @covers Gaufrette\File
     */
    public function shouldGetPassedFilesystem()
    {
        $filesystem = $this->getFilesystemMock();

        $file = new File('myFile', $filesystem);
        $this->assertSame($filesystem, $file->getFilesystem());
    }

    /**
     * @test
     * @covers Gaufrette\File
     */
    public function shouldReadContentOnlyOnce()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
           ->method('has')
           ->with($this->equalTo('myFile'))
           ->will($this->returnValue(true));
        $fs->expects($this->once())
           ->method('read')
           ->with($this->equalTo('myFile'))
           ->will($this->returnValue('test content'));

        $file = new File('myFile', $fs);

        $this->assertEquals('test content', $file->getContent('myFile'));
        $this->assertEquals('test content', $file->getContent('myFile'));
    }

    /**
     * @test
     * @covers Gaufrette\File
     * @expectedException \LogicException
     */
    public function shouldFailWhenGetMetadataForFilesystemWhichDoNotSupportIt()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->any())
           ->method('supportsMetadata')
           ->will($this->returnValue(false));

        $file = new File('myFile', $fs);
        $file->getMetadata();
    }

    /**
     * @test
     * @covers Gaufrette\File
     * @expectedException \LogicException
     */
    public function shouldFailWhenSetMetadataForFilesystemWhichDoNotSupportIt()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->any())
           ->method('supportsMetadata')
           ->will($this->returnValue(false));

        $file = new File('myFile', $fs);
        $file->setMetadata(array('test' => 'aaa'));
    }

    /**
     * @test
     * @covers Gaufrette\File
     */
    public function shouldAllowSetAndGetMetadataWhenFilesystemSupportIt()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->any())
           ->method('supportsMetadata')
           ->will($this->returnValue(true));

        $metadata = array('test' => 'aaa');

        $file = new File('myFile', $fs);
        $file->setMetadata($metadata);

        $this->assertEquals($metadata, $file->getMetadata());
    }

    /**
     * @test
     * @covers Gaufrette\File
     */
    public function shouldAllowSetAndGetName()
    {
        $file = new File('myFile', $this->getFilesystemMock());
        $file->setName('fileName');

        $this->assertEquals('fileName', $file->getName());
    }

    /**
     * @test
     * @covers Gaufrette\File
     */
    public function shouldAllowForSetAndGetCreatedDate()
    {
        $dateTime = new \DateTime();
        $file = new File('myFile', $this->getFilesystemMock());
        $file->setCreated($dateTime);

        $this->assertSame($dateTime, $file->getCreated());
    }

    /**
     * @test
     * @covers Gaufrette\File
     */
    public function shouldAllowForSetAndGetSize()
    {
        $file = new File('myFile', $this->getFilesystemMock());
        $file->setSize(123);

        $this->assertEquals(123, $file->getSize());
    }

    /**
     * @test
     * @covers Gaufrette\File
     * @expectedException \LogicException
     */
    public function shouldFailWhenTryBeReadedWhenDoesNotExist()
    {
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
     * @covers Gaufrette\File
     */
    public function shouldWriteContentInFilesystem()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
           ->method('write')
           ->with($this->equalTo('myFile'), $this->equalTo('some content'));

        $file = new File('myFile', $fs);
        $file->setContent('some content');
    }

    /**
     * @test
     * @covers Gaufrette\File
     * @expectedException \LogicException
     */
    public function shouldNotBeDeletedWhenDoesNotExist()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
           ->method('has')
           ->will($this->returnValue(false));

        $file = new File('myFile', $fs);
        $file->delete();
    }

    /**
     * @test
     * @covers Gaufrette\File
     */
    public function shouldBeDeletedFromFilesystem()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
           ->method('has')
           ->will($this->returnValue(true));
        $fs->expects($this->once())
           ->method('delete')
           ->with($this->equalTo('myFile'))
           ->will($this->returnValue(true));

        $file = new File('myFile', $fs);
        $this->assertTrue($file->delete());
    }

    /**
     * @test
     * @covers Gaufrette\File
     */
    public function shouldCreateFilestreamFromFilesystem()
    {
        $fs = $this->getFilesystemMock();
        $fs->expects($this->once())
           ->method('createFileStream')
           ->will($this->returnValue('file stream'));

        $file = new File('myFile', $fs);
        $this->assertEquals('file stream', $file->createFileStream());
    }

    protected function getFilesystemMock()
    {
        return $this->getMock('Gaufrette\Filesystem', array(), array(), '', false);
    }
}
