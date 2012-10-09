<?php

namespace spec\Gaufrette\Util;

use PHPSpec2\ObjectBehavior;

class Size extends ObjectBehavior
{
    function it_should_calculate_size_of_content()
    {
        $this->fromContent('some content')->shouldReturn(12);
        $this->fromContent('some other content')->shouldReturn(18);
        $this->fromContent('some')->shouldReturn(4);
    }
}
