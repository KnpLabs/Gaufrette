<?php

namespace Gaufrette\Adapter;

class ZipTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension should be enabled');
        }
        ZipMock::$zipArchiveObject = null;
    }

    public function tearDown()
    {
        ZipMock::$zipArchiveObject = null;
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     * @dataProvider getInitErrorResults
     */
    public function shouldFailWhenCannotInitZipArchive($resultCode, $expectedMessage)
    {
        $this->setExpectedException('RuntimeException', $expectedMessage);

        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue($resultCode));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     */
    public function shouldReadPackedFileContent()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'getFromName', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('getFromName')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('read content'));
        $zipArchive
            ->expects($this->any())
            ->method('statName')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(array(
                'name' => 'foo',
                'index' => 3,
                'crc' => 499465816,
                'size' => 27,
                'mtime' => 1123164748,
                'comp_size' => 24,
                'comp_method' => 8
            )));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $this->assertEquals('read content', $zip->read('foo'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     * @expectedException \RuntimeException
     */
    public function shouldFailWhenCannotReadPackedFileContent()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'getFromName', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('getFromName')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(false));
        $zipArchive
            ->expects($this->any())
            ->method('statName')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(array(
                'name' => 'foo',
                'index' => 3,
                'crc' => 499465816,
                'size' => 27,
                'mtime' => 1123164748,
                'comp_size' => 24,
                'comp_method' => 8
            )));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $zip->read('foo');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     * @expectedException \RuntimeException
     */
    public function shouldFailWhenCannotWriteContentToFile()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'addFromString', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('addFromString')
            ->with($this->equalTo('foo'), 'write content')
            ->will($this->returnValue(false));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $zip->write('foo', 'write content');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     */
    public function shouldWriteContentToFile()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'addFromString', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('close')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('addFromString')
            ->with($this->equalTo('foo'), 'write content')
            ->will($this->returnValue(true));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $this->assertEquals(13, $zip->write('foo', 'write content'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     * @expectedException \RuntimeException
     */
    public function shouldFailWhenCannotCloseAndSaveArchive()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'addFromString', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('close')
            ->will($this->returnValue(false));
        $zipArchive
            ->expects($this->any())
            ->method('addFromString')
            ->with($this->equalTo('foo'), 'write content')
            ->will($this->returnValue(true));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $this->assertEquals(13, $zip->write('foo', 'write content'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     * @expectedException \RuntimeException
     */
    public function shouldFailWhenDeletingFileDoesNotExist()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('close')
            ->will($this->returnValue(false));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $zip->delete('foo');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     * @expectedException \RuntimeException
     */
    public function shouldFailWhenCannotDeleteFile()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'deleteName', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('close')
            ->will($this->returnValue(false));
        $zipArchive
            ->expects($this->any())
            ->method('deleteName')
            ->will($this->returnValue(false));
        $zipArchive
            ->expects($this->any())
            ->method('statName')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(array(
                'name' => 'foo',
                'index' => 3,
                'crc' => 499465816,
                'size' => 27,
                'mtime' => 1123164748,
                'comp_size' => 24,
                'comp_method' => 8
            )));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $zip->delete('foo');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     * @expectedException \RuntimeException
     */
    public function shouldFailWhenCannotRenameFile()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'renameName', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('close')
            ->will($this->returnValue(false));
        $zipArchive
            ->expects($this->any())
            ->method('renameName')
            ->will($this->returnValue(false));
        $zipArchive
            ->expects($this->at(1))
            ->method('statName')
            ->will($this->returnValue(array(
                'name' => 'foo',
                'index' => 3,
                'crc' => 499465816,
                'size' => 27,
                'mtime' => 1123164748,
                'comp_size' => 24,
                'comp_method' => 8
            )));
        $zipArchive
            ->expects($this->at(2))
            ->method('statName')
            ->will($this->returnValue(false));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $zip->rename('foo', 'bar');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Zip
     * @todo getStat should be protected
     * @expectedException \RuntimeException
     */
    public function shouldFailWhenCannotGetFileStat()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('statName')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(false));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $zip->getStat('foo', true);
    }

    /**
     * @test
     * @todo getStat should be protected
     * @covers Gaufrette\Adapter\Zip
     */
    public function shouldNotFailWhenCannotGetFileStat()
    {
        $zipArchive = $this->getMockBuilder('ZipArchive')
            ->setMethods(array('open', 'statName', 'close'))
            ->getMock();
        $zipArchive
            ->expects($this->any())
            ->method('open')
            ->will($this->returnValue(true));
        $zipArchive
            ->expects($this->any())
            ->method('statName')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(false));

        ZipMock::$zipArchiveObject = $zipArchive;

        $zip = new ZipMock('test');
        $this->assertFalse($zip->getStat('foo', false));
    }

    public function getInitErrorResults()
    {
        return array(
            array(\ZipArchive::ER_EXISTS, 'File already exists.'),
            array(\ZipArchive::ER_INCONS, 'Zip archive inconsistent.'),
            array(\ZipArchive::ER_INVAL, 'Invalid argument.'),
            array(\ZipArchive::ER_MEMORY, 'Malloc failure.'),
            array(\ZipArchive::ER_NOENT, 'Invalid argument.'),
            array(\ZipArchive::ER_NOZIP, 'Not a zip archive.'),
            array(\ZipArchive::ER_OPEN, 'Can\'t open file.'),
            array(\ZipArchive::ER_READ, 'Read error.'),
            array(\ZipArchive::ER_SEEK, 'Seek error.'),
            array('some error', 'Unknown error.')
        );
    }
}

class ZipMock extends Zip
{
    public static $zipArchiveObject;

    protected function createZipArchiveObject()
    {
        return static::$zipArchiveObject;
    }
}
