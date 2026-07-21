<?php

namespace spec\Gaufrette;

use Gaufrette\FilesystemMap;
use Gaufrette\Filesystem;
use Gaufrette\Stream;
use Gaufrette\StreamWrapper;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class StreamWrapperSpec extends ObjectBehavior
{
    public function let(FilesystemMap $map, Filesystem $filesystem, Stream $stream): void
    {
        $filesystem->createStream('filename')->willReturn($stream);
        $map->get('some')->willReturn($filesystem);
        $this->setFilesystemMap($map);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(StreamWrapper::class);
    }

    public function it_opens_stream(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);

        $this
            ->stream_open('gaufrette://some/filename', 'r+', STREAM_REPORT_ERRORS)
            ->shouldReturn(true)
        ;
    }

    public function it_does_not_open_stream_when_key_is_not_defined(): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('The specified path (gaufrette://some) is invalid.'))
            ->duringStream_open('gaufrette://some', 'r+', STREAM_REPORT_ERRORS);
    }

    public function it_does_not_open_stream_when_host_is_not_defined(): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('The specified path (gaufrette:///somefile) is invalid.'))
            ->duringStream_open('gaufrette:///somefile', 'r+', STREAM_REPORT_ERRORS)
        ;
    }

    public function it_does_not_read_from_stream_when_is_not_opened(): void
    {
        $this->stream_read(10)->shouldReturn(false);
    }

    public function it_does_not_read_from_stream(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->read(4)->willReturn('some');

        $this->stream_open('gaufrette://some/filename', 'r+', STREAM_REPORT_ERRORS);
        $this->stream_read(4)->shouldReturn('some');
    }

    public function it_does_not_write_to_stream_when_is_not_opened(): void
    {
        $this->stream_write('some content')->shouldReturn(0);
    }

    public function it_writes_to_stream(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->write('some content')->shouldBeCalled()->willReturn(12);

        $this->stream_open('gaufrette://some/filename', 'w+', STREAM_REPORT_ERRORS);
        $this->stream_write('some content')->shouldReturn(12);
    }

    public function it_does_not_close_stream_when_is_not_opened($stream): void
    {
        $stream->close()->shouldNotBeCalled();
        $this->stream_close();
    }

    public function it_closes_stream(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->close()->shouldBeCalled();
        $this->stream_open('gaufrette://some/filename', 'w+', STREAM_REPORT_ERRORS);
        $this->stream_close();
    }

    public function it_does_not_flush_stream_when_is_not_opened(Stream $stream): void
    {
        $stream->flush()->shouldNotBeCalled();
        $this->stream_flush();
    }

    public function it_flushes_stream(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->flush()->shouldBeCalled();
        $this->stream_open('gaufrette://some/filename', 'w+', STREAM_REPORT_ERRORS);
        $this->stream_flush();
    }

    public function it_does_not_seek_in_stream_when_is_not_opened(Stream $stream): void
    {
        $stream->seek(12, SEEK_SET)->shouldNotBeCalled();
        $this->stream_seek(12, SEEK_SET);
    }

    public function it_seeks_in_stream(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->seek(12, SEEK_SET)->shouldBeCalled()->willReturn(true);
        $this->stream_open('gaufrette://some/filename', 'w+', STREAM_REPORT_ERRORS);
        $this->stream_seek(12, SEEK_SET)->shouldReturn(true);
    }

    public function it_does_not_tell_about_position_in_stream_when_is_not_opened(Stream $stream): void
    {
        $stream->tell()->shouldNotBeCalled();
        $this->stream_tell();
    }

    public function it_does_tell_about_position_in_stream(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->tell()->shouldBeCalled()->willReturn(12);
        $this->stream_open('gaufrette://some/filename', 'w+', STREAM_REPORT_ERRORS);
        $this->stream_tell()->shouldReturn(12);
    }

    public function it_does_not_mark_as_eof_if_stream_is_not_opened(Stream $stream): void
    {
        $stream->eof()->shouldNotBeCalled();
        $this->stream_eof();
    }

    public function it_checks_if_eof(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);
        $this->stream_open('gaufrette://some/filename', 'w+', STREAM_REPORT_ERRORS);
        $stream->eof()->willReturn(false);

        $this->stream_eof()->shouldReturn(false);

        $stream->eof()->willReturn(true);
        $this->stream_eof()->shouldReturn(true);
    }

    public function it_does_not_get_stat_when_is_not_open(): void
    {
        $this->stream_stat()->shouldReturn(false);
    }

    public function it_stats_file(Stream $stream): void
    {
        $stat = [
            'dev' => 1,
            'ino' => 12,
            'mode' => 0777,
            'nlink' => 0,
            'uid' => 123,
            'gid' => 1,
            'rdev' => 0,
            'size' => 666,
            'atime' => 1348030800,
            'mtime' => 1348030800,
            'ctime' => 1348030800,
            'blksize' => 5,
            'blocks' => 1,
        ];
        $stream->open(Argument::any())->willReturn(true);
        $stream->stat()->willReturn($stat);

        $this->stream_open('gaufrette://some/filename', 'w+', STREAM_REPORT_ERRORS);
        $this->stream_stat()->shouldReturn($stat);
    }

    public function it_should_stat_from_url(Stream $stream): void
    {
        $stat = [
            'dev' => 1,
            'ino' => 12,
            'mode' => 0777,
            'nlink' => 0,
            'uid' => 123,
            'gid' => 1,
            'rdev' => 0,
            'size' => 666,
            'atime' => 1348030800,
            'mtime' => 1348030800,
            'ctime' => 1348030800,
            'blksize' => 5,
            'blocks' => 1,
        ];
        $stream->open(Argument::any())->willReturn(true);
        $stream->stat()->willReturn($stat);

        $this->url_stat('gaufrette://some/filename', STREAM_URL_STAT_LINK)->shouldReturn($stat);
    }

    public function it_stats_even_if_it_cannot_be_open(Filesystem $filesystem, Stream $stream): void
    {
        $filesystem->createStream('dir/')->willReturn($stream);
        $stream->open(Argument::any())->willThrow(new \RuntimeException);
        $stream->stat(Argument::any())->willReturn(['mode' => 16893]);
        $this->url_stat('gaufrette://some/dir/', STREAM_URL_STAT_LINK)->shouldReturn(['mode' => 16893]);
    }

    public function it_does_not_unlink_when_cannot_open(Stream $stream): void
    {
        $stream->open(Argument::any())->willThrow(new \RuntimeException);
        $this->unlink('gaufrette://some/filename')->shouldReturn(false);
    }

    public function it_unlinks_file(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->unlink()->willReturn(true);

        $this->unlink('gaufrette://some/filename')->shouldReturn(true);
    }

    public function it_does_not_cast_stream_if_is_not_opened(): void
    {
        $this->stream_cast(STREAM_CAST_FOR_SELECT)->shouldReturn(false);
    }

    public function it_casts_stream(Stream $stream): void
    {
        $stream->open(Argument::any())->willReturn(true);
        $stream->cast(STREAM_CAST_FOR_SELECT)->willReturn('resource');

        $this->stream_open('gaufrette://some/filename', 'w+', STREAM_REPORT_ERRORS);
        $this->stream_cast(STREAM_CAST_FOR_SELECT)->shouldReturn('resource');
    }
}
