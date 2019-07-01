<?php

namespace spec\Gaufrette\Adapter;

use Gaufrette\Exception\FileNotFound;
use PhpSpec\ObjectBehavior;

class InMemorySpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith([
            'filename' => ['mtime' => 12345, 'content' => 'content'],
            'filename2' => 'other content',
        ]);
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_is_a_mime_type_provider()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MimeTypeProvider');
    }

    function it_gets_the_file_mime_type()
    {
        $this->mimeType('filename')->shouldReturn('text/plain');
    }

    function it_reads_file()
    {
        $this->read('filename')->shouldReturn('content');
    }

    function it_writes_file()
    {
        $this->write('filename', 'some content');
    }

    function it_renames_file()
    {
        $this->rename('filename', 'aaa/filename2');

        $this->exists('filename')->shouldReturn(false);
        $this->exists('aaa/filename2')->shouldReturn(true);
    }

    function it_checks_if_file_exists()
    {
        $this->exists('filename')->shouldReturn(true);
        $this->exists('filenameTest')->shouldReturn(false);
    }

    function it_fetches_keys()
    {
        $this->keys()->shouldReturn(['filename', 'filename2']);
    }

    function it_fetches_mtime()
    {
        $this->mtime('filename')->shouldReturn(12345);
    }

    function it_deletes_file()
    {
        $this->shouldNotThrow(FileNotFound::class)->duringDelete('filename');
    }

    function it_throws_file_not_found_exception_when_file_does_not_exist()
    {
        $this->shouldThrow(FileNotFound::class)->duringDelete('does-not-exist');
    }

    function it_does_not_handle_dirs()
    {
        $this->isDirectory('filename')->shouldReturn(false);
        $this->isDirectory('filename2')->shouldReturn(false);
    }
}
