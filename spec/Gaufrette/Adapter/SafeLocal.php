<?php

namespace spec\Gaufrette\Adapter;

//hack - mock php built-in functions
require_once 'functions.php';

use PHPSpec2\ObjectBehavior;

class SafeLocal extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('/home/l3l0');
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\SafeLocal');
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_should_compute_path_using_base64()
    {
        $this->read('filename')->shouldReturn('/home/l3l0/'.base64_encode('filename').' content');
    }

    function it_should_compute_key_back_using_base64()
    {
        global $iteratorToArray;
        $iteratorToArray = array('/home/l3l0/'.base64_encode('filename'), '/home/l3l0/'.base64_encode('filename1'), '/home/l3l0/'.base64_encode('aaa/filename'));

        $this->keys()->shouldReturn(array('aaa', 'aaa/filename', 'filename', 'filename1'));
    }
}
