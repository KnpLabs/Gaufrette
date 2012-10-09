<?php

namespace spec\Gaufrette\Adapter;

//hack - mock php built-in functions
require_once 'functions.php';

use PHPSpec2\ObjectBehavior;

class Ftp extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('/home/l3l0', 'localhost');
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\Ftp');
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_should_check_if_file_exists()
    {
        $this->exists('filename')->shouldReturn(true);
        $this->exists('aa/filename')->shouldReturn(false);
    }

    function it_should_read_file()
    {
        $this->read('filename')->shouldReturn('some content');
    }

    function it_should_not_read_file()
    {
        $this->read('filename2')->shouldReturn(false);
    }

    function it_should_write_file()
    {
        $this->write('filename', 'some content')->shouldReturn(12);
    }

    function it_should_not_write_file()
    {
        $this->write('filename2', 'some content')->shouldReturn(false);
    }

    function it_should_rename_file()
    {
        $this->rename('filename', 'filename2')->shouldReturn(true);
    }

    function it_should_not_rename_file()
    {
        $this->rename('filename', 'invalid')->shouldReturn(false);
    }

    function it_should_fetch_keys()
    {
        $this->keys()->shouldReturn(array('filename', 'aaa', 'aaa/filename'));
    }

    function it_should_get_mtime()
    {
        $this->mtime('filename')->shouldReturn(strtotime('2010-10-10 23:10:10'));
    }

    function it_should_throw_excention_when_mtime_is_not_supported_by_server()
    {
        $this->shouldThrow(new \RuntimeException('Server does not support ftp_mdtm function.'))->duringMtime('invalid');
    }

    function it_should_delete_file()
    {
        $this->delete('filename')->shouldReturn(true);
    }

    function it_should_not_delete_file()
    {
        $this->delete('invalid')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Filesystem $filesystem
     */
    function it_should_create_file($filesystem)
    {
        $this->createFile('filename', $filesystem)->shouldReturnAnInstanceOf('\Gaufrette\File');
    }

    function it_should_check_is_directory()
    {
        $this->isDirectory('aaa')->shouldReturn(true);
        $this->isDirectory('filename')->shouldReturn(false);
    }
}
