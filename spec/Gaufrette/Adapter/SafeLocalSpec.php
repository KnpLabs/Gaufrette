<?php

namespace spec\Gaufrette\Adapter;

//hack - mock php built-in functions
require_once 'functions.php';

use PhpSpec\ObjectBehavior;

class SafeLocalSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('/home/l3l0');
    }

    function it_is_local_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter\Local');
    }

    function it_computes_path_using_base64()
    {
        $this->read('filename')->shouldReturn('/home/l3l0/'.base64_encode('filename').' content');
    }

    function it_computes_key_back_using_base64()
    {
        global $iteratorToArray;
        $iteratorToArray = array('/home/l3l0/'.base64_encode('filename'), '/home/l3l0/'.base64_encode('filename1'), '/home/l3l0/'.base64_encode('aaa/filename'));

        $this->keys()->shouldReturn(array('aaa', 'aaa/filename', 'filename', 'filename1'));
    }
}
