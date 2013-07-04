<?php

namespace spec\Gaufrette;

use PHPSpec2\ObjectBehavior;

class StreamWrapper extends ObjectBehavior
{
    /**
     * @param \Gaufrette\FilesystemMap $map
     * @param \Gaufrette\Filesystem    $filesystem
     * @param \Gaufrette\Stream        $stream
     */
    function let($map, $filesystem, $stream)
    {
        $filesystem->createStream('filename')->willReturn($stream);
        $map->get('some')->willReturn($filesystem);
        $this->setFilesystemMap($map);
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\StreamWrapper');
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_open_stream($stream)
    {
        $stream->open(ANY_ARGUMENT)->willReturn(true);

        $this->stream_open('gaufrette://some/filename', 'r+')->shouldReturn(true);
    }

    function it_should_not_open_stream_when_key_is_not_defined()
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('The specified path (gaufrette://some) is invalid.'))
            ->duringStream_open('gaufrette://some', 'r+');
    }

    function it_should_not_open_stream_when_host_is_not_defined()
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('The specified path (gaufrette:///somefile) is invalid.'))
            ->duringStream_open('gaufrette:///somefile', 'r+');
    }

    function it_should_not_read_from_stream_when_is_not_opened()
    {
        $this->stream_read(10)->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_read_from_stream($stream)
    {
        $stream->read(4)->willReturn('some');

        $this->stream_open('gaufrette://some/filename', 'r+');
        $this->stream_read(4)->shouldReturn('some');
    }

    function it_should_not_write_to_stream_when_is_not_opened()
    {
        $this->stream_write('some content')->shouldReturn(0);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_write_to_stream($stream)
    {
        $stream->write('some content')->shouldBeCalled()->willReturn(12);

        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_write('some content')->shouldReturn(12);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_not_close_stream_when_is_not_opened($stream)
    {
        $stream->close()->shouldNotBeCalled();
        $this->stream_close();
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_close_stream($stream)
    {
        $stream->close()->shouldBeCalled();
        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_close();
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_not_flush_stream_when_is_not_opened($stream)
    {
        $stream->flush()->shouldNotBeCalled();
        $this->stream_flush();
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_flush_stream($stream)
    {
        $stream->flush()->shouldBeCalled();
        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_flush();
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_not_seek_in_stream_when_is_not_opened($stream)
    {
        $stream->seek(12, SEEK_SET)->shouldNotBeCalled();
        $this->stream_seek(12, SEEK_SET);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_seek_in_stream($stream)
    {
        $stream->seek(12, SEEK_SET)->shouldBeCalled()->willReturn(true);
        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_seek(12, SEEK_SET)->shouldReturn(true);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_not_tell_about_position_in_stream_when_is_not_opened($stream)
    {
        $stream->tell()->shouldNotBeCalled();
        $this->stream_tell();
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_tell_about_position_in_stream($stream)
    {
        $stream->tell()->shouldBeCalled()->willReturn(12);
        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_tell()->shouldReturn(12);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_mark_as_eof_if_stream_is_not_opened($stream)
    {
        $stream->eof()->shouldNotBeCalled();
        $this->stream_eof();
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_check_if_eof_in_stream($stream)
    {
        $this->stream_open('gaufrette://some/filename', 'w+');
        $stream->eof()->willReturn(false);

        $this->stream_eof()->shouldReturn(false);

        $stream->eof()->willReturn(true);
        $this->stream_eof()->shouldReturn(true);
    }

    function it_should_not_get_stat_when_is_not_open()
    {
        $this->stream_stat()->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_stat_file_from_stream($stream)
    {
        $stat = array(
            'dev'   => 1,
            'ino'   => 12,
            'mode'  => 0777,
            'nlink' => 0,
            'uid'   => 123,
            'gid'   => 1,
            'rdev'  => 0,
            'size'  => 666,
            'atime' => 1348030800,
            'mtime' => 1348030800,
            'ctime' => 1348030800,
            'blksize' => 5,
            'blocks'  => 1,
        );
        $stream->stat()->willReturn($stat);

        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_stat()->shouldReturn($stat);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_stat_from_url($stream)
    {
        $stat = array(
            'dev'   => 1,
            'ino'   => 12,
            'mode'  => 0777,
            'nlink' => 0,
            'uid'   => 123,
            'gid'   => 1,
            'rdev'  => 0,
            'size'  => 666,
            'atime' => 1348030800,
            'mtime' => 1348030800,
            'ctime' => 1348030800,
            'blksize' => 5,
            'blocks'  => 1,
        );
        $stream->stat()->willReturn($stat);

        $this->url_stat('gaufrette://some/filename', STREAM_URL_STAT_LINK)->shouldReturn($stat);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_not_stat_when_cannot_open($stream)
    {
        $stream->open(ANY_ARGUMENT)->willThrow(new \RuntimeException);
        $this->url_stat('gaufrette://some/filename', STREAM_URL_STAT_LINK)->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_not_unlink_when_cannot_open($stream)
    {
        $stream->open(ANY_ARGUMENT)->willThrow(new \RuntimeException);
        $this->unlink('gaufrette://some/filename')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_unlink($stream)
    {
        $stream->unlink()->willReturn(true);

        $this->unlink('gaufrette://some/filename')->shouldReturn(true);
    }

    function it_should_not_cast_stream_if_is_not_opened()
    {
        $this->stream_cast(STREAM_CAST_FOR_SELECT)->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Stream $stream
     */
    function it_should_cast_stream($stream)
    {
        $stream->cast(STREAM_CAST_FOR_SELECT)->willReturn('resource');

        $this->stream_open('gaufrette://some/filename', 'w+');
        $this->stream_cast(STREAM_CAST_FOR_SELECT)->shouldReturn('resource');
    }
}
