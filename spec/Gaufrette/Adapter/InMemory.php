<?php

namespace spec\Gaufrette\Adapter;

use PHPSpec2\ObjectBehavior;

class InMemory extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(array(
            'filename'  => array('mtime' => 12345, 'content' => 'content'),
            'filename2' => 'other content'
        ));
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\InMemory');
    }

    function it_should_read_file()
    {
        $this->read('filename')->shouldReturn('content');
    }

    function it_should_write_file()
    {
        $this->write('filename', 'some content')->shouldReturn(12);
    }

    function it_should_rename_file()
    {
         $this->rename('filename', 'aaa/filename2')->shouldReturn(true);
         $this->exists('filename')->shouldReturn(false);
         $this->exists('aaa/filename2')->shouldReturn(true);
    }

    function it_should_check_if_file_exists()
    {
        $this->exists('filename')->shouldReturn(true);
        $this->exists('filenameTest')->shouldReturn(false);
    }

    function it_should_get_keys()
    {
        $this->keys()->shouldReturn(array('filename', 'filename2'));
    }

    function it_should_get_mtime()
    {
        $this->mtime('filename')->shouldReturn(12345);
    }

    function it_should_delete_file()
    {
        $this->delete('filename')->shouldReturn(true);
        $this->exists('filename')->shouldReturn(false);
    }

    function it_should_not_handle_dirs()
    {
        $this->isDirectory('filename')->shouldReturn(false);
        $this->isDirectory('filename2')->shouldReturn(false);
    }
}
