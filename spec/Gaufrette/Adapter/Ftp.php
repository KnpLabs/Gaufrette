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

    function it_should_check_if_file_exists_for_absolute_path()
    {
        $this->exists('filename')->shouldReturn(true);
        $this->exists('aa/filename')->shouldReturn(false);
    }

    function it_should_check_if_file_exists_for_relative_path()
    {
        $this->beConstructedWith('/home/l3l0/relative', 'localhost');

        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename2')->shouldReturn(false);
        $this->exists('aa/filename')->shouldReturn(false);
        $this->exists('some/otherfilename')->shouldReturn(true);
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

    function it_should_fetch_keys_without_directories_dots()
    {
        $this->keys()->shouldReturn(array('filename', 'filename.exe', '.htaccess', 'aaa', 'aaa/filename'));
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

    /**
     * @param \Gaufrette\Filesystem $filesystem
     */
    function it_should_create_file_in_not_existing_directory($filesystem)
    {
        $this->createFile('bb/cc/dd/filename', $filesystem)->shouldReturnAnInstanceOf('\Gaufrette\File');
    }

    function it_should_check_is_directory()
    {
        $this->isDirectory('aaa')->shouldReturn(true);
        $this->isDirectory('filename')->shouldReturn(false);
    }

    function it_should_fetch_keys_with_hidden_files()
    {
        $this->beConstructedWith('/home/l3l1', 'localhost');

        $this->keys()->shouldReturn(array('filename', '.htaccess'));
    }

    function it_should_check_if_hidden_file_exists()
    {
        $this->beConstructedWith('/home/l3l1', 'localhost');

        $this->exists('.htaccess')->shouldReturn(true);
    }

    function it_should_create_base_directory_without_warning()
    {
        global $createdDirectory;
        $createdDirectory = '';

        $this->beConstructedWith('/home/l3l0/new', 'localhost', array('create' => true));

        $this->listDirectory()->shouldReturn(array('keys' => array(), 'dirs' => array()));
    }

    function it_should_not_create_base_directory_and_should_throw_exception()
    {
        global $createdDirectory;
        $createdDirectory = '';

        $this->beConstructedWith('/home/l3l0/new', 'localhost', array('create' => false));

        $this->shouldThrow(new \RuntimeException("The directory '/home/l3l0/new' does not exist."))->during('listDirectory', array());
    }

    function it_should_fetch_keys_for_windows()
    {
        $this->beConstructedWith('C:\Ftp', 'localhost');

        $this->keys()->shouldReturn(array('archive', 'file1.zip', 'file2.zip'));
    }
}
