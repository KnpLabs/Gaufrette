<?php

namespace spec\Gaufrette\Util;

use PhpSpec\ObjectBehavior;

class PathSpec extends ObjectBehavior
{
    public function it_checks_if_path_is_absolute(): void
    {
        $this->isAbsolute('/home/path')->shouldBe(true);
        $this->isAbsolute('home/path')->shouldBe(false);
        $this->isAbsolute('../home/path')->shouldBe(false);
        $this->isAbsolute('protocol://home/path')->shouldBe(true);
    }

    public function it_normalizes_file_path(): void
    {
        $this->normalize('C:\\some\other.txt')->shouldReturn('c:/some/other.txt');
        $this->normalize('..\other.txt')->shouldReturn('../other.txt');
        $this->normalize('..\other.txt')->shouldReturn('../other.txt');
        $this->normalize('/home/other/../new')->shouldReturn('/home/new');
        $this->normalize('/home/other/./new')->shouldReturn('/home/other/new');
        $this->normalize('protocol://home/other.txt')->shouldReturn('protocol://home/other.txt');
    }

    public function it_returns_unix_style_dirname(): void
    {
        $this->dirname('a/test/path')->shouldReturn('a/test');
    }
}
