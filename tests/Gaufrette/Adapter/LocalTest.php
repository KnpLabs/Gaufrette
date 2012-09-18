<?php

namespace Gaufrette\Adapter;

class LocalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     */
    public function shouldReadLocalFile()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('exists', 'getFileContents', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->once())
            ->method('exists')
            ->with($this->equalTo('test.txt'))
            ->will($this->returnValue(true));
        $localAdapter->expects($this->once())
            ->method('getFileContents')
            ->with($this->equalTo('/home/aaa/test.txt'))
            ->will($this->returnValue('Some local file'));

        $this->assertSame('Some local file', $localAdapter->read('test.txt'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function shouldFailWhenFileToReadDoesNotExist()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('exists', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->once())
            ->method('exists')
            ->with($this->equalTo('test.txt'))
            ->will($this->returnValue(false));

        $this->assertSame('Some local file', $localAdapter->read('test.txt'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Could not read the 'test.txt' file
     */
    public function shouldFailWhenCannotReadFile()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('exists', 'getFileContents', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->once())
            ->method('exists')
            ->with($this->equalTo('test.txt'))
            ->will($this->returnValue(true));
        $localAdapter->expects($this->once())
            ->method('getFileContents')
            ->will($this->returnValue(false));

        $localAdapter->read('test.txt');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     */
    public function shouldWriteContentToLocalFile()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('setFileContents', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->once())
            ->method('setFileContents')
            ->with('/home/aaa/test.txt', 'Some local file')
            ->will($this->returnValue(10));

        $this->assertEquals(10, $localAdapter->write('test.txt', 'Some local file'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Could not write the 'test.txt' file
     */
    public function shouldFailWhenCannotWriteToFile()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('setFileContents', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->once())
            ->method('setFileContents')
            ->with('/home/aaa/test.txt', 'Some local file')
            ->will($this->returnValue(false));

        $localAdapter->write('test.txt', 'Some local file');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     */
    public function shouldRenameFile()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('exists', 'renameFile', 'assertExists', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->any())
            ->method('exists')
            ->with($this->equalTo('target.txt'))
            ->will($this->returnValue(false));
        $localAdapter->expects($this->once())
            ->method('renameFile')
            ->with($this->equalTo('/home/aaa/from.txt'), $this->equalTo('/home/aaa/target.txt'))
            ->will($this->returnValue(true));

        $localAdapter->rename('from.txt', 'target.txt');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException Gaufrette\Exception\UnexpectedFile
     */
    public function shouldNotRenameFileWhenTargetExists()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('exists', 'renameFile', 'assertExists', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->any())
            ->method('exists')
            ->with($this->equalTo('target.txt'))
            ->will($this->returnValue(true));
        $localAdapter->expects($this->never())
            ->method('renameFile');

        $localAdapter->rename('from.txt', 'target.txt');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not rename the "from.txt" file to "target.txt".
     */
    public function shouldFailWhenCannotRenameFile()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('exists', 'renameFile', 'assertExists', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->any())
            ->method('exists')
            ->with($this->equalTo('target.txt'))
            ->will($this->returnValue(false));
        $localAdapter->expects($this->once())
            ->method('renameFile')
            ->with($this->equalTo('/home/aaa/from.txt'), $this->equalTo('/home/aaa/target.txt'))
            ->will($this->returnValue(false));

        $localAdapter->rename('from.txt', 'target.txt');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     */
    public function shouldDeleteFile()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('exists', 'deleteFile', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->any())
            ->method('exists')
            ->with($this->equalTo('some.txt'))
            ->will($this->returnValue(true));
        $localAdapter->expects($this->once())
            ->method('deleteFile')
            ->with($this->equalTo('/home/aaa/some.txt'))
            ->will($this->returnValue(true));

        $localAdapter->delete('some.txt');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not remove the 'some.txt' file.
     */
    public function shouldFailWhenCannotDeleteFile()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('exists', 'deleteFile', 'ensureDirectoryExists'), array('/home/aaa', false));
        $localAdapter->expects($this->any())
            ->method('exists')
            ->with($this->equalTo('some.txt'))
            ->will($this->returnValue(true));
        $localAdapter->expects($this->once())
            ->method('deleteFile')
            ->with($this->equalTo('/home/aaa/some.txt'))
            ->will($this->returnValue(false));

        $localAdapter->delete('some.txt');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage The file 'test.txt' is out of the filesystem.
     */
    public function shouldFailWhenPathIsOutOfDirectory()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('ensureDirectoryExists', 'normalizePath'), array('/home/aaa', false));
        $localAdapter->expects($this->once())
            ->method('normalizePath')
            ->will($this->returnValue('/zzz'));

        $localAdapter->computePath('test.txt');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     */
    public function shouldComputePath()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('ensureDirectoryExists'), array('/home/aaa', false));

        $this->assertEquals('/home/aaa/test.txt', $localAdapter->computePath('test.txt'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage The path '/zzz' is out of the filesystem.
     */
    public function shouldFailWhenKeyIsOutOfDirectory()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('ensureDirectoryExists', 'normalizePath'), array('/home/aaa', false));
        $localAdapter->expects($this->once())
            ->method('normalizePath')
            ->will($this->returnValue('/zzz'));

        $localAdapter->computeKey('test.txt');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     */
    public function shouldComputeKey()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('ensureDirectoryExists'), array('/home/aaa', false));

        $this->assertEquals('test.txt', $localAdapter->computeKey('/home/aaa/test.txt'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException RuntimeException
     * @expectedExceptionMessage The directory '/home/aaa' does not exist.
     */
    public function shouldFailWhenDirectoryDoesNotExist()
    {
        $localAdapter = $this->getMockBuilder('Gaufrette\Adapter\Local')
            ->setMethods(array('isDirectory'))
            ->disableOriginalConstructor()
            ->getMock();

        $localAdapter->expects($this->any())
            ->method('isDirectory')
            ->will($this->returnValue(false));

        $localAdapter->ensureDirectoryExists('/home/aaa', false);
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     */
    public function shouldCreateDirectoryWhenDoesNotExist()
    {
        $localAdapter = $this->getMockBuilder('Gaufrette\Adapter\Local')
            ->setMethods(array('isDirectory', 'createDirectory'))
            ->disableOriginalConstructor()
            ->getMock();

        $localAdapter->expects($this->any())
            ->method('isDirectory')
            ->will($this->returnValue(false));
        $localAdapter->expects($this->once())
            ->method('createDirectory');

        $localAdapter->ensureDirectoryExists('/home/aaa', true);
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     */
    public function shouldCreateDirectory()
    {
        $localAdapter = $this->getMockBuilder('Gaufrette\Adapter\Local')
            ->setMethods(array('mkdir', 'isDirectory'))
            ->disableOriginalConstructor()
            ->getMock();

        $localAdapter->expects($this->once())
            ->method('isDirectory')
            ->will($this->returnValue(false));
        $localAdapter->expects($this->once())
            ->method('mkdir')
            ->with($this->equalTo('/home/aaa'), $this->equalTo(0777), $this->equalTo(true))
            ->will($this->returnValue(true));

        $localAdapter->createDirectory('/home/aaa');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The directory '/home/aaa' already exists.
     */
    public function shouldFailWhenDirectoryExists()
    {
        $localAdapter = $this->getMockBuilder('Gaufrette\Adapter\Local')
            ->setMethods(array('mkdir', 'isDirectory'))
            ->disableOriginalConstructor()
            ->getMock();

        $localAdapter->expects($this->once())
            ->method('isDirectory')
            ->will($this->returnValue(true));

        $localAdapter->createDirectory('/home/aaa');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Local
     * @expectedException RuntimeException
     * @expectedExceptionMessage The directory '/home/aaa' could not be created.
     */
    public function shouldFailWhenCannotCreateDirectory()
    {
        $localAdapter = $this->getMockBuilder('Gaufrette\Adapter\Local')
            ->setMethods(array('mkdir', 'isDirectory'))
            ->disableOriginalConstructor()
            ->getMock();

        $localAdapter->expects($this->once())
            ->method('isDirectory')
            ->will($this->returnValue(false));
        $localAdapter->expects($this->once())
            ->method('mkdir')
            ->with($this->equalTo('/home/aaa'), $this->equalTo(0777), $this->equalTo(true))
            ->will($this->returnValue(false));

        $localAdapter->createDirectory('/home/aaa');
    }

    /**
     * @test
     */
    public function shouldCreateLocalFileStream()
    {
        $localAdapter = $this->getMock('Gaufrette\Adapter\Local', array('ensureDirectoryExists'), array('/home/aaa', false));
        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertInstanceOf('Gaufrette\FileStream\Local', $localAdapter->createFileStream('aaa', $filesystem));
    }
}
