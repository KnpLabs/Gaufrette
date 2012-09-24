<?php

namespace Gaufrette\FileStream;

use Gaufrette\Filesystem;
use Gaufrette\Adapter;
use Gaufrette\StreamMode;

class LocalTest extends \PHPUnit_Framework_TestCase
{
    private $filePath;

    public function setUp()
    {
        $this->filePath = __DIR__.'localStreamTestFile.txt';
    }

    public function tearDown()
    {
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     * @expectedException \RuntimeException
     */
    public function shouldNotBeAbleToOpenFileToReadWhenDoesNotExist()
    {
        $stream  = new Local($this->filePath);
        $this->assertFalse($stream->open(new StreamMode('r')));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldBeAbleToOpenFileToRead()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $this->assertTrue($stream->open(new StreamMode('r')));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldBeAbleToReadFile()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('r'));

        $this->assertEquals('Some', $stream->read(4));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotFailWhenReadFileWasNotOpen()
    {
        $stream  = new Local($this->filePath);

        $this->assertFalse($stream->read(4));
    }

    /**
     * @test
     * @dataProvider getNotReadableModes
     * @expectedException LogicException
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldFailWhenReadIsNotAllowed($mode)
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode($mode));
        $stream->read(4);
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldBeAbleToWriteFile()
    {
        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('w'));
        $stream->write('Other content');

        $this->assertEquals('Other content', file_get_contents($this->filePath));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotFailWhenWriteFileWasNotOpen()
    {
        $stream  = new Local($this->filePath);

        $this->assertFalse($stream->write('Other content'));
    }

    /**
     * @test
     * @expectedException LogicException
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldFailWhenWriteIsNotAllowed()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('r'));
        $stream->write('Other content');
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldCloseTheFileHandler()
    {
        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('w'));

        $this->assertTrue($stream->close());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotFailWhenClosingFileWasNotOpen()
    {
        $stream  = new Local($this->filePath);

        $this->assertFalse($stream->close());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldFlushTheFile()
    {
        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('w'));

        $this->assertTrue($stream->flush());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotFailWhenFlushingFileWasNotOpen()
    {
        $stream  = new Local($this->filePath);

        $this->assertFalse($stream->flush());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldSeekInFile()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('r+'));

        $this->assertTrue($stream->seek(1));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotFailWhenSeekingFileWasNotOpen()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $this->assertFalse(false, $stream->seek(1));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldGetCurrentPositionInFileStream()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('r+'));
        $stream->seek(3);

        $this->assertEquals(3, $stream->tell());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotFailWhenTellingFileWasNotOpen()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $this->assertFalse($stream->tell());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldCheckEOFInStream()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('r'));
        $stream->seek(13);
        $stream->read(1);

        $this->assertTrue($stream->eof());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotFailWhenEOFCheckingFileWasNotOpen()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $this->assertTrue($stream->eof());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldGetStatFileInfo()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('w+'));

        $this->assertInternalType('array', $stream->stat());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotFailWhenStatingFileWasNotOpen()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $this->assertFalse($stream->stat());
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldCastToResourseType()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('r+'));
        $this->assertInternalType('resource', $stream->cast(STREAM_CAST_FOR_SELECT));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotFailWhenCastingFileWasNotOpen()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $this->assertFalse($stream->cast(STREAM_CAST_FOR_SELECT));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldBeAbleToUnlinkFile()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('w+'));

        $this->assertTrue($stream->unlink());
        $this->assertFalse(is_file($this->filePath));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotUnlinkFileWhenNotOpened()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);

        $this->assertFalse($stream->unlink($this->filePath));
        $this->assertTrue(is_file($this->filePath));
    }

    /**
     * @test
     * @covers Gaufrette\FileStream\Local
     */
    public function shouldNotUnlinkWhenDoNotImpliesContentDeletion()
    {
        file_put_contents($this->filePath, 'Some contents');

        $stream  = new Local($this->filePath);
        $stream->open(new StreamMode('r'));

        $this->assertFalse($stream->unlink($this->filePath));
        $this->assertTrue(is_file($this->filePath));
    }

    public function getNotReadableModes()
    {
        return array(
            array('w'),
            array('a'),
            array('c'),
        );
    }
}
