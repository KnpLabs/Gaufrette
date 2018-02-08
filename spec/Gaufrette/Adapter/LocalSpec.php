<?php

namespace spec\Gaufrette\Adapter;

use Gaufrette\Exception\InvalidKey;
use Gaufrette\Exception\StorageFailure;
use org\bovigo\vfs\vfsStream;
use PhpSpec\ObjectBehavior;

class LocalSpec extends ObjectBehavior
{
    function let()
    {
        vfsStream::setup('test');
        vfsStream::copyFromFileSystem(__DIR__.'/MockFilesystem');

        $this->beConstructedWith(vfsStream::url('test'));
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_is_checksum_calculator()
    {
        $this->shouldHaveType('Gaufrette\Adapter\ChecksumCalculator');
    }

    function it_is_a_mime_type_provider()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MimeTypeProvider');
    }

    function it_gets_the_file_mime_type()
    {
        $this->mimeType('filename')->shouldReturn('text/plain');
    }

    function it_is_stream_factory()
    {
        $this->shouldHaveType('Gaufrette\Adapter\StreamFactory');
    }

    function it_reads_file()
    {
        $this->read('filename')->shouldReturn("content\n");
    }

    function it_writes_file()
    {
        $this->shouldNotThrow(StorageFailure::class)->duringWrite('filename', 'some content');
    }

    function it_renames_file()
    {
        $this->shouldNotThrow(StorageFailure::class)->duringRename('filename', 'aaa/filename2');
    }

    function it_checks_if_file_exists()
    {
        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename1')->shouldReturn(false);
    }

    function it_fetches_keys()
    {
        $expectedKeys = array('filename', 'dir', 'dir/file');
        sort($expectedKeys);
        $this->keys()->shouldReturn($expectedKeys);
    }

    function it_fetches_mtime()
    {
        $mtime = filemtime(vfsStream::url('test/filename'));
        $this->mtime('filename')->shouldReturn($mtime);
    }

    function it_deletes_file()
    {
        $this->shouldNotThrow(StorageFailure::class)->duringDelete('filename');
        $this->shouldThrow(StorageFailure::class)->duringDelete('filename1');
    }

    function it_checks_if_given_key_is_directory()
    {
        $this->isDirectory('dir')->shouldReturn(true);
        $this->isDirectory('filename')->shouldReturn(false);
    }

    function it_creates_local_stream()
    {
        $this->createStream('filename')->shouldReturnAnInstanceOf('Gaufrette\Stream\Local');
    }

    function it_does_not_allow_to_read_path_above_main_file_directory()
    {
        $this
            ->shouldThrow(InvalidKey::class)
            ->duringRead('../filename')
        ;
        $this
            ->shouldThrow(InvalidKey::class)
            ->duringExists('../filename')
        ;
    }
}
