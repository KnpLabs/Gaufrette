<?php

namespace Gaufrette;

use Gaufrette\Adapter\InMemory;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldAllowToGetAdapter()
    {
        $adapter = new InMemory(array());
        $fs = new Filesystem($adapter);

        $this->assertSame($adapter, $fs->getAdapter());
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldCheckIfFileExists()
    {
        $adapter = new InMemory(array('filename1' => array()));
        $fs = new Filesystem($adapter);

        $this->assertTrue($fs->has('filename1'));
        $this->assertFalse($fs->has('filename'));
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldRenameFile()
    {
        $adapter = new InMemory(array('filename1' => array()));

        $fs = new Filesystem($adapter);
        $fs->rename('filename1', 'filename');

        $this->assertFalse($fs->has('filename1'));
        $this->assertTrue($fs->has('filename'));
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldGetAFileInstanceConfiguredForTheKeyAndFilesystem()
    {
        $adapter = new InMemory(array(
            'myFile' => array()
        ));

        $fs = new Filesystem($adapter);

        $file = $fs->get('myFile');

        $this->assertInstanceOf('Gaufrette\File', $file);
        $this->assertEquals('myFile', $file->getKey());
        $this->assertEquals($fs, $file->getFilesystem());
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     * @expectedException \InvalidArgumentException
     */
    public function shouldFailWhenTryGetTheFileWhichDoesNotExist()
    {
        $adapter = new InMemory();

        $fs = new Filesystem($adapter);
        $fs->get('myFile');
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     * @expectedException \InvalidArgumentException
     */
    public function shouldFailWhenTryReadTheFileWhichDoesNotExist()
    {
        $adapter = new InMemory();

        $fs = new Filesystem($adapter);
        $fs->read('myFile');
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     * @expectedException \InvalidArgumentException
     */
    public function shouldFailWhenTryWriteFileWhichExistsAndCannotBeOverwrite()
    {
        $adapter = new InMemory(array(
            'myFile' => array()
        ));

        $fs = new Filesystem($adapter);
        $fs->write('myFile', 'some text');
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldDeleteFile()
    {
        $adapter = new InMemory(array(
            'myFile' => array()
        ));

        $fs = new Filesystem($adapter);
        $fs->delete('myFile');

        $this->assertFalse($fs->has('myFile'));
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     * @expectedException \InvalidArgumentException
     */
    public function shouldFailDeletingFileDoesNotExist()
    {
        $adapter = new InMemory(array(
            'myFile' => array()
        ));

        $fs = new Filesystem($adapter);
        $fs->delete('test');
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldGetFilesNames()
    {
        $adapter = new InMemory(array(
            'myFile' => array(),
            'myFile2' => array()
        ));

        $fs = new Filesystem($adapter);
        $this->assertEquals(array('myFile', 'myFile2'), $fs->keys());
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldUseAdapterListDirectoryMethodIfReturnsList()
    {
        $adapter = $this->getMock('Gaufrette\Adapter');
        $adapter->expects($this->any())
            ->method('listDirectory')
            ->with($this->equalTo('testDir'))
            ->will($this->returnValue(array('aaa', 'bbb')));

        $fs = new Filesystem($adapter);
        $this->assertEquals(array('aaa', 'bbb'), $fs->listDirectory('testDir'));
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldUseKeysIfAdapterListDirectoryNotDefined()
    {
        $adapter = $this->getMock('Gaufrette\Adapter');
        $adapter->expects($this->any())
            ->method('listDirectory')
            ->with($this->equalTo('testDir'))
            ->will($this->returnValue(false));
        $adapter->expects($this->any())
            ->method('keys')
            ->will($this->returnValue(array('aaa', 'bbb')));

        $fs = new Filesystem($adapter);
        $this->assertEquals(array('keys' => array('aaa', 'bbb'), 'dirs' => array()), $fs->listDirectory('testDir'));
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldGetMtimeFromAdapter()
    {
        $adapter = $this->getMock('Gaufrette\Adapter');
        $adapter->expects($this->any())
            ->method('mtime')
            ->with($this->equalTo('test'))
            ->will($this->returnValue(1348475944));

        $fs = new Filesystem($adapter);
        $this->assertEquals(1348475944, $fs->mtime('test'));
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldGetChecksumFromAdapter()
    {
        $adapter = $this->getMock('Gaufrette\Adapter');
        $adapter->expects($this->any())
            ->method('checksum')
            ->with($this->equalTo('test'))
            ->will($this->returnValue('134847aa'));

        $fs = new Filesystem($adapter);
        $this->assertEquals('134847aa', $fs->checksum('test'));
    }

    /**
     * @test
     * @covers Gaufrette\Filesystem
     */
    public function shouldCheckIfAdapterSupportsMetadata()
    {
        $adapter = $this->getMock('Gaufrette\Adapter');
        $adapter->expects($this->at(0))
            ->method('supportsMetadata')
            ->will($this->returnValue(false));
        $adapter->expects($this->at(1))
            ->method('supportsMetadata')
            ->will($this->returnValue(true));

        $fs = new Filesystem($adapter);
        $this->assertFalse($fs->supportsMetadata());
        $this->assertTrue($fs->supportsMetadata());
    }
}
