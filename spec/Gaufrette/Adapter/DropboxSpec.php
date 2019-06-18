<?php

namespace spec\Gaufrette\Adapter;

use Dropbox_API;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DropboxSpec extends ObjectBehavior
{
    /**
     * @param \Dropbox_API $dropbox
     */
    function let(Dropbox_API $dropbox)
    {
        $this->beConstructedWith($dropbox);
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_reads_file(Dropbox_API $dropbox)
    {
        $dropbox->getFile('filename')->willReturn('some content');

        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_does_not_mask_exception_from_client_during_read(Dropbox_API $dropbox)
    {
        $dropbox->getFile('filename')->willThrow(new \RuntimeException('read'));

        $this->shouldThrow(new \RuntimeException('read'))->duringRead('filename');
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_does_not_read_file(Dropbox_API $dropbox)
    {
        $dropbox
            ->getFile('filename')
            ->willThrow(new \Dropbox_Exception_NotFound());

        $this->read('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_checks_if_file_exists(Dropbox_API $dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willReturn([
                'size' => '225.4KB',
                'rev' => '35e97029684fe',
                'thumb_exists' => false,
                'bytes' => 230783,
                'modified' => 'Tue, 19 Jul 2011 21:55:38 +0000',
                'client_mtime' => 'Mon, 18 Jul 2011 18:04:35 +0000',
                'path' => '/filename',
                'is_dir' => false,
                'icon' => 'page_white_acrobat',
                'root' => 'dropbox',
                'mime_type' => 'application/pdf',
                'revision' => 220823,
            ]);

        $this->exists('filename')->shouldReturn(true);

        $dropbox
            ->getMetaData('filename', false)
            ->willThrow(new \Dropbox_Exception_NotFound);

        $this->exists('filename')->shouldReturn(false);

        $dropbox
            ->getMetaData('filename', false)
            ->willReturn(['is_deleted' => true]);

        $this->exists('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_does_not_mask_exception_from_client_during_check_if_exists(Dropbox_API $dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willThrow(new \RuntimeException('exists'));

        $this->shouldThrow(new \RuntimeException('exists'))->duringExists('filename');
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_gets_keys(Dropbox_API $dropbox)
    {
        $dropbox
            ->getMetaData('/', true)
            ->willReturn([
                'contents' => [
                    ['path' => '/filename'],
                    ['path' => '/aaa/filename'],
                ],
            ]);

        $this->keys()->shouldReturn(['aaa', 'aaa/filename', 'filename']);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_does_not_mask_exception_from_client_during_getting_keys(Dropbox_API $dropbox)
    {
        $dropbox
            ->getMetaData('/', true)
            ->willThrow(new \RuntimeException('keys'))
        ;

        $this->shouldThrow(new \RuntimeException('keys'))->duringKeys();
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_checks_if_given_key_is_directory(Dropbox_API $dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willReturn([
                'is_dir' => true,
            ])
        ;

        $this->isDirectory('filename')->shouldReturn(true);

        $dropbox
            ->getMetaData('filename', false)
            ->willReturn([
                'is_dir' => false,
            ]);

        $this->isDirectory('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_writes_file(Dropbox_API $dropbox)
    {
        $dropbox
            ->putFile('filename', Argument::any())
            ->shouldBeCalled()
        ;

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_does_not_mask_exception_from_client_during_write(Dropbox_API $dropbox)
    {
        $dropbox
            ->putFile('filename', Argument::any())
            ->willThrow(new \RuntimeException('write'))
        ;

        $this->shouldThrow(new \RuntimeException('write'))->duringWrite('filename', 'some content');
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_deletes_file(Dropbox_API $dropbox)
    {
        $dropbox
            ->delete('filename')
            ->shouldBeCalled()
        ;

        $this->delete('filename')->shouldReturn(true);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_does_not_delete_file(Dropbox_API $dropbox)
    {
        $dropbox
            ->delete('filename')
            ->willThrow(new \Dropbox_Exception_NotFound())
        ;

        $this->delete('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_renames_file(Dropbox_API $dropbox)
    {
        $dropbox
            ->move('filename', 'filename2')
            ->shouldBeCalled()
        ;

        $this->rename('filename', 'filename2')->shouldReturn(true);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_does_not_rename_file(Dropbox_API $dropbox)
    {
        $dropbox
            ->move('filename', 'filename2')
            ->willThrow(new \Dropbox_Exception_NotFound())
        ;

        $this->rename('filename', 'filename2')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_fetches_mtime(Dropbox_API $dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willReturn([
                'modified' => 'Tue, 19 Jul 2011 21:55:38 +0000',
            ])
        ;

        $this->mtime('filename')->shouldReturn(1311112538);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_does_not_fetch_mtime_when_file_not_found(Dropbox_API $dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willThrow(new \Dropbox_Exception_NotFound())
        ;

        $this->mtime('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_does_not_check_if_key_is_dir_when_file_not_found(Dropbox_API $dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willThrow(new \Dropbox_Exception_NotFound())
        ;

        $this->isDirectory('filename')->shouldReturn(false);
    }

    /**
     * @param \Dropbox_API $dropbox
     */
    function it_fails_checking_if_key_is_dir_when_dropbox_throws_exception(Dropbox_API $dropbox)
    {
        $dropbox
            ->getMetaData('filename', false)
            ->willThrow(new \RuntimeException('some exception'))
        ;

        $this->shouldThrow(new \RuntimeException('some exception'))->duringIsDirectory('filename');
    }
}
