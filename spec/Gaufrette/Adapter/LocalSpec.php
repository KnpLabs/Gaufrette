<?php

namespace spec\Gaufrette\Adapter;

use bovigo\vfs\vfsStream;
use PhpSpec\ObjectBehavior;

class LocalSpec extends ObjectBehavior
{
    public function let(): void
    {
        vfsStream::setup('test');
        vfsStream::copyFromFileSystem(__DIR__ . '/MockFilesystem');
        $this->beConstructedWith(vfsStream::url('test'));
    }

    public function it_is_adapter(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter::class);
    }

    public function it_is_checksum_calculator(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\ChecksumCalculator::class);
    }

    public function it_is_a_mime_type_provider(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\MimeTypeProvider::class);
    }

    public function it_gets_the_file_mime_type(): void
    {
        $this->mimeType('filename')->shouldReturn('text/plain');
    }

    public function it_is_stream_factory(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\StreamFactory::class);
    }

    public function it_reads_file(): void
    {
        $this->read('filename')->shouldReturn("content\n");
    }

    public function it_writes_file(): void
    {
        $this->write('filename', 'some content')->shouldReturn(12);
    }

    public function it_renames_file(): void
    {
        $this->rename('filename', 'aaa/filename2')->shouldReturn(true);
    }

    public function it_checks_if_file_exists(): void
    {
        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename1')->shouldReturn(false);
    }

    public function it_fetches_keys(): void
    {
        $expectedKeys = ['filename', 'dir', 'dir/file'];
        sort($expectedKeys);
        $this->keys()->shouldReturn($expectedKeys);
    }

    public function it_fetches_mtime(): void
    {
        $mtime = filemtime(vfsStream::url('test/filename'));
        $this->mtime('filename')->shouldReturn($mtime);
    }

    public function it_deletes_file(): void
    {
        $this->delete('filename')->shouldReturn(true);
        $this->delete('filename1')->shouldReturn(false);
    }

    public function it_deletes_dir(): void
    {
        $this->delete('dir')->shouldReturn(true);
    }

    public function it_checks_if_given_key_is_directory(): void
    {
        $this->isDirectory('dir')->shouldReturn(true);
        $this->isDirectory('filename')->shouldReturn(false);
    }

    public function it_creates_local_stream(): void
    {
        $this->createStream('filename')->shouldReturnAnInstanceOf(\Gaufrette\Stream\Local::class);
    }

    public function it_does_not_allow_to_read_path_above_main_file_directory(): void
    {
        $this
            ->shouldThrow(new \OutOfBoundsException(sprintf('The path "%s" is out of the filesystem.', vfsStream::url('filename'))))
            ->duringRead('../filename')
        ;
        $this
            ->shouldThrow(new \OutOfBoundsException(sprintf('The path "%s" is out of the filesystem.', vfsStream::url('filename'))))
            ->duringExists('../filename')
        ;
    }

    public function it_fails_when_directory_does_not_exists(): void
    {
        $this->beConstructedWith(vfsStream::url('other'));

        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringRead('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringWrite('filename', 'some content')
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringRename('filename', 'otherFilename')
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringExists('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringKeys()
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringMtime('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringDelete('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringIsDirectory('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringCreateStream('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringChecksum('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException(sprintf('The directory "%s" does not exist.', vfsStream::url('other'))))
            ->duringMimeType('filename')
        ;
    }

    public function it_creates_directory_when_does_not_exists(): void
    {
        $this->beConstructedWith(vfsStream::url('test/other'), true);

        $this->isDirectory('/')->shouldReturn(true);
    }
}
