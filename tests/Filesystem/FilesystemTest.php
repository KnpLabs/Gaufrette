<?php

namespace Gaufrette\Filesystem;

class TestAdapter implements Adapter
{
    public function read($key) {}
    public function write($key, $content) {}
    public function exists($key) {}
    public function list($pattern) {}
}

class FilesystemTest extends \PHPUnit_Framwork_TestCase
{
    public function testGetReturnsAFileInstanceConfiguredForTheKeyAndFilesystem()
    {
        $adapter = $this->getAdapterMock();
        $adapter->expects($this->once())
                ->method('exists')
                ->with($this->equalTo('myFile'))
                ->will($this->returnValue(true));

        $fs = new Filesystem();

        $file = $fs->get('myFile');

        $this->assertInstanceOf('Gaufrette\Filesystem\File', $file);
        $this->assertEquals('myFile', $file->getKey());
        $this->assertEquals($fs, $file->getFilesystem());
    }

    public function testGetThrowsAnExceptionIfTheFileDoesNotExistAndTheCreateParameterIsSetToFalse()
    {
        $adapter = $this->getAdapterMock();
        $adapter->expects($this->once())
                ->method('exists')
                ->with($this->equalTo('myFile'))
                ->will($this->returnValue(false));

        $fs = new Filesystem($adapter);

        $this->setExcpectedException('InvalidArgumentException');

        $fs->get('myFile');
    }

    public function testReadThrowsAnExceptionIfTheKeyDoesNotMatchAnyFile()
    {
        $adapter = $this->getAdapterMock();
        $adapter->expects($this->once())
                ->method('exists')
                ->with($this->equalTo('myFile'))
                ->will($this->returnValue(false));

        $fs = new Filesystem($adapter);

        $this->setExcpectedException('InvalidArgumentException');

        $fs->read('myFile');
    }

    public function testWriteThrowsAnExceptionIfTheFileAlreadyExistsAndIsNotAllowedToOverwrite()
    {
        $adapter = $this->getAdapterMock();
        $adapter->expects($this->once())
                ->method('exists')
                ->with($this->equalTo('myFile'))
                ->will($this->returnValue(true));

        $fs = new Filesystem($adapter);

        $this->setExcpectedException('InvalidArgumentException');

        $fs->write('myFile', 'some text');
    }

    protected function getAdapterMock()
    {
        return $this->getMock('Gaufrette\Filesystem\TestAdapter');
    }
}
