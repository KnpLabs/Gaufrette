<?php

namespace spec\Gaufrette\Util;

use PHPSpec2\ObjectBehavior;

class Path extends ObjectBehavior
{
    function it_should_check_if_path_is_absolute()
    {
        $this->isAbsolute('/home/path')->shouldBe(true);
        $this->isAbsolute('home/path')->shouldBe(false);
        $this->isAbsolute('../home/path')->shouldBe(false);
    }

    function it_should_normalize_file_path()
    {
        $this->normalize('C:\\some\other.txt')->shouldReturn('c:/some/other.txt');
        $this->normalize('..\other.txt')->shouldReturn('../other.txt');
        $this->normalize('..\other.txt')->shouldReturn('../other.txt');
        $this->normalize('/home/other/../new')->shouldReturn('/home/new');
        $this->normalize('/home/other/./new')->shouldReturn('/home/other/new');
    }
}
