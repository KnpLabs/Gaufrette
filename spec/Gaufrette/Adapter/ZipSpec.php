<?php

namespace spec\Gaufrette\Adapter;

use Gaufrette\Adapter;
use PhpSpec\ObjectBehavior;

class ZipSpec extends ObjectBehavior
{
    public function let(): void
    {
        $this->beConstructedWith('somefile');
    }

    public function it_is_adapter(): void
    {
        $this->shouldHaveType(Adapter::class);
    }
}
