<?php

namespace spec\src\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AwsS3Spec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('src\Gaufrette\Adapter\AwsS3.php');
    }
}
