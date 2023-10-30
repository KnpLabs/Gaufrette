<?php

namespace spec\Gaufrette\Adapter;

use Gaufrette\Adapter;
use PhpSpec\ObjectBehavior;

class ZipSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('somefile');
    }

    function it_is_adapter()
    {
        $this->shouldHaveType(Adapter::class);
    }
}
