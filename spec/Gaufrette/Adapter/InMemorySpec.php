<?php

namespace spec\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;

class InMemorySpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith([
            'filename' => ['mtime' => 12345, 'content' => 'content'],
            'filename2' => 'other content',
        ]);
    }

    public function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    public function it_is_a_mime_type_provider()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MimeTypeProvider');
    }

    public function it_gets_the_file_mime_type()
    {
        $this->mimeType('filename')->shouldReturn('text/plain');
    }

    public function it_reads_file()
    {
        $this->read('filename')->shouldReturn('content');
    }

    public function it_writes_file()
    {
        $this->write('filename', 'some content')->shouldReturn(12);
    }

    public function it_renames_file()
    {
        $this->rename('filename', 'aaa/filename2')->shouldReturn(true);
        $this->exists('filename')->shouldReturn(false);
        $this->exists('aaa/filename2')->shouldReturn(true);
    }

    public function it_checks_if_file_exists()
    {
        $this->exists('filename')->shouldReturn(true);
        $this->exists('filenameTest')->shouldReturn(false);
    }

    public function it_fetches_keys()
    {
        $this->keys()->shouldReturn(['filename', 'filename2']);
    }

    public function it_fetches_mtime()
    {
        $this->mtime('filename')->shouldReturn(12345);
    }

    public function it_deletes_file()
    {
        $this->delete('filename')->shouldReturn(true);
        $this->exists('filename')->shouldReturn(false);
    }

    public function it_does_not_handle_dirs()
    {
        $this->isDirectory('filename')->shouldReturn(false);
        $this->isDirectory('filename2')->shouldReturn(false);
    }
}
