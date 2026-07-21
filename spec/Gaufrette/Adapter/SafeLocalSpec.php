<?php

namespace spec\Gaufrette\Adapter;

use bovigo\vfs\vfsStream;
use PhpSpec\ObjectBehavior;

class SafeLocalSpec extends ObjectBehavior
{
    public function let(): void
    {
        vfsStream::setup('test');
        vfsStream::copyFromFileSystem(__DIR__ . '/MockFilesystem');
        $this->beConstructedWith(vfsStream::url('test'));
    }

    public function it_is_local_adapter(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\Local::class);
    }

    public function it_computes_path_using_base64(): void
    {
        rename(vfsStream::url('test/filename'), vfsStream::url('test/' . base64_encode('filename')));
        $this->read('filename')->shouldReturn("content\n");
    }

    public function it_computes_key_back_using_base64(): void
    {
        $this->keys()->shouldReturn([base64_decode('dir'), base64_decode('dir/file'), base64_decode('filename')]);
    }
}
