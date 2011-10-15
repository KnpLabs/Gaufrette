<?php

namespace Gaufrette\FileStream;

use Gaufrette\Filesystem;
use Gaufrette\Adapter;

class InMemoryBufferTest extends \PHPUnit_Framework_TestCase
{
    public function testOpenInRMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('r'));
        $this->assertStreamStatus('Some content', 0, true, false, true, $stream);
        $this->assertEquals('Some content', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertFalse($stream->open('r'));
        $this->assertStreamStatus(null, null, null, null, null, $stream);
        $this->assertFalse($file->exists());
    }

    public function testOpenInRPlusMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('r+'));
        $this->assertStreamStatus('Some content', 0, true, true, true, $stream);
        $this->assertEquals('Some content', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertFalse($stream->open('r+'));
        $this->assertStreamStatus(null, null, null, null, null, $stream);
        $this->assertFalse($file->exists());
    }

    public function testOpenInWMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('w'));
        $this->assertStreamStatus('', 0, false, true, true, $stream);
        $this->assertEquals('', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('w'));
        $this->assertStreamStatus('', 0, false, true, true, $stream);
        $this->assertTrue($file->exists());
        $this->assertEquals('', $file->getContent());
    }

    public function testOpenInWPlusMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('w+'));
        $this->assertStreamStatus('', 0, true, true, true, $stream);
        $this->assertEquals('', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('w+'));
        $this->assertStreamStatus('', 0, true, true, true, $stream);
        $this->assertTrue($file->exists());
        $this->assertEquals('', $file->getContent());
    }

    public function testOpenInAMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('a'));
        $this->assertStreamStatus('Some content', 12, false, true, true, $stream);
        $this->assertEquals('Some content', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('a'));
        $this->assertStreamStatus('', 0, false, true, true, $stream);
        $this->assertTrue($file->exists());
        $this->assertEquals('', $file->getContent());
    }

    public function testOpenInAPlusMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('a+'));
        $this->assertStreamStatus('Some content', 12, true, true, true, $stream);
        $this->assertEquals('Some content', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('a+'));
        $this->assertStreamStatus('', 0, true, true, true, $stream);
        $this->assertTrue($file->exists());
        $this->assertEquals('', $file->getContent());
    }

    public function testOpenInXMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertFalse($stream->open('x'));
        $this->assertStreamStatus(null, null, null, null, null, $stream);
        $this->assertEquals('Some content', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('x'));
        $this->assertStreamStatus('', 0, false, true, true, $stream);
        $this->assertTrue($file->exists());
        $this->assertEquals('', $file->getContent());
    }

    public function testOpenInXPlusMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertFalse($stream->open('x+'));
        $this->assertStreamStatus(null, null, null, null, null, $stream);
        $this->assertEquals('Some content', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('x+'));
        $this->assertStreamStatus('', 0, true, true, true, $stream);
        $this->assertTrue($file->exists());
        $this->assertEquals('', $file->getContent());
    }

    public function testOpenInCMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('c'));
        $this->assertStreamStatus('Some content', 0, false, true, true, $stream);
        $this->assertEquals('Some content', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('c'));
        $this->assertStreamStatus('', 0, false, true, true, $stream);
        $this->assertTrue($file->exists());
        $this->assertEquals('', $file->getContent());
    }

    public function testOpenInCPlusMode()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('c+'));
        $this->assertStreamStatus('Some content', 0, true, true, true, $stream);
        $this->assertEquals('Some content', $file->getContent());

        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $this->assertTrue($stream->open('c+'));
        $this->assertStreamStatus('', 0, true, true, true, $stream);
        $this->assertTrue($file->exists());
        $this->assertEquals('', $file->getContent());
    }

    public function testWrite()
    {
        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $stream->open('w');
        $stream->write('Some content');
        $this->assertStreamStatus('Some content', 12, false, true, false, $stream);
        $this->assertEquals('', $file->getContent());

        $file = $this->createExistingFile('foo content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $stream->open('r+');
        $stream->write('bar');
        $this->assertStreamStatus('bar content', 3, true, true, false, $stream);

        $file = $this->createExistingFile('some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $stream->open('r+');
        $stream->write('some new content');
        $this->assertStreamStatus('some new content', 16, true, true, false, $stream);
    }

    /**
     * @expectedException LogicException
     */
    public function testWriteWhenAllowWriteEqualsFalse()
    {
        $file = $this->createExistingFile('Some content');
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $stream->open('r');
        $stream->write('Some new content');
    }

    public function testFlush()
    {
        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $stream->open('w');
        $stream->write('Some content');
        $this->assertEquals('', $file->getFilesystem()->read($file->getKey()));
        $stream->flush();
        $this->assertEquals('Some content', $file->getFilesystem()->read($file->getKey()));
    }

    public function testClose()
    {
        $file = $this->createNonExistingFile();
        $stream = new InMemoryBuffer($file->getKey(), $file->getFilesystem());
        $stream->open('w');
        $stream->write('Some content');
        $this->assertEquals('', $file->getFilesystem()->read($file->getKey()));
        $stream->close();
        $this->assertEquals('Some content', $file->getFilesystem()->read($file->getKey()));
    }

    private function assertStreamStatus($content, $position, $allowRead, $allowWrite, $synchronized, $stream)
    {
        $this->assertAttributeEquals($content, 'content', $stream, 'The content is valid');
        $this->assertAttributeEquals($position, 'position', $stream, 'The position is valid');
        $this->assertAttributeEquals($allowRead, 'allowRead', $stream, 'The allow-read status is valid');
        $this->assertAttributeEquals($allowWrite, 'allowWrite', $stream, 'The allow-write status is valid');
        $this->assertAttributeEquals($synchronized, 'synchronized', $stream, 'The synchronization status is valid');
    }

    private function createExistingFile($content)
    {
        $filesystem = new Filesystem(new Adapter\InMemory());
        $filesystem->write('foo', $content);

        return $filesystem->get('foo');
    }

    private function createNonExistingFile()
    {
        $filesystem = new Filesystem(new Adapter\InMemory());

        return $filesystem->get('foo', true);
    }
}
