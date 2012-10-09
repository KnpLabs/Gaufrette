<?php

namespace spec\Gaufrette\Adapter;

use PHPSpec2\ObjectBehavior;

class Zip extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('somefile');
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\Zip');
        $this->shouldHaveType('Gaufrette\Adapter');
    }
}
