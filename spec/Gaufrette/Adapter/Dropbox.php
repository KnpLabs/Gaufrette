<?php

namespace spec\Gaufrette\Adapter;

use PHPSpec2\ObjectBehavior;

class Dropbox extends ObjectBehavior
{
    /**
     * @param \Dropbox_API $dropbox
     */
    function let($dropbox)
    {
        $this->beConstructedWith($dropbox);
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\Dropbox');
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_read_file($dropbox)
    {
        $dropbox->getFile('filename')->willReturn('some content');

        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_not_fail_when_cannot_read_at_dropbox($dropbox)
    {
        $dropbox->getFile('filename')->willThrow(new \Exception);

        $this->read('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_check_if_file_exists($dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willReturn(array(
                "size"         => "225.4KB",
                "rev"          => "35e97029684fe",
                "thumb_exists" => false,
                "bytes"        => 230783,
                "modified"     => "Tue, 19 Jul 2011 21:55:38 +0000",
                "client_mtime" => "Mon, 18 Jul 2011 18:04:35 +0000",
                "path"         => "/filename",
                "is_dir"       => false,
                "icon"         => "page_white_acrobat",
                "root"         => "dropbox",
                "mime_type"    => "application/pdf",
                "revision"     => 220823
            ));

        $this->exists('filename')->shouldReturn(true);

        $dropbox
            ->getMetaData('filename', false)
            ->willThrow(new \Dropbox_Exception_NotFound);

        $this->exists('filename')->shouldReturn(false);

        $dropbox
            ->getMetaData('filename', false)
            ->willReturn(array("is_deleted" => true));

        $this->exists('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_get_keys($dropbox)
    {
        $dropbox
            ->getMetaData('/', true)
            ->willReturn(array(
                'contents' => array(
                    array('path' => '/filename'),
                    array('path' => '/aaa/filename')
                )
            ));

        $this->keys()->shouldReturn(array('aaa', 'aaa/filename', 'filename'));
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_check_is_directory($dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willReturn(array(
                "is_dir" => true
            ));

        $this->isDirectory('filename')->shouldReturn(true);

        $dropbox
            ->getMetaData('filename', false)
            ->willReturn(array(
                "is_dir" => false
            ));

        $this->isDirectory('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_write_file($dropbox)
    {
        $dropbox
            ->putFile('filename', ANY_ARGUMENT)
            ->shouldBeCalled();

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_not_write_file($dropbox)
    {
        $dropbox
            ->putFile('filename', ANY_ARGUMENT)
            ->willThrow(new \Exception);

        $this->write('filename', 'some content')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_delete_file($dropbox)
    {
        $dropbox
            ->delete('filename')
            ->shouldBeCalled();

        $this->delete('filename')->shouldReturn(true);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_not_delete_file($dropbox)
    {
        $dropbox
            ->delete('filename')
            ->willThrow(new \Exception);

        $this->delete('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_rename_file($dropbox)
    {
        $dropbox
            ->move('filename', 'filename2')
            ->shouldBeCalled();

        $this->rename('filename', 'filename2')->shouldReturn(true);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_not_rename_file($dropbox)
    {
        $dropbox
            ->move('filename', 'filename2')
            ->willThrow(new \Exception);

        $this->rename('filename', 'filename2')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_should_get_mtime($dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willReturn(array(
                "modified"     => "Tue, 19 Jul 2011 21:55:38 +0000",
            ));

        $this->mtime('filename')->shouldReturn(1311112538);
    }
}
