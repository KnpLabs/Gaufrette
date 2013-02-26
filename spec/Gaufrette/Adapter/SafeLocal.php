<?php

namespace spec\Gaufrette\Adapter;

//hack - mock php built-in functions
require_once 'functions.php';

use PHPSpec2\ObjectBehavior;

class SafeLocal extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(sys_get_temp_dir().'/l3l0');
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\SafeLocal');
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_should_compute_path_using_base64()
    {
        $this->read('filename')->shouldReturn(sys_get_temp_dir().'/l3l0/'.base64_encode('filename').' content');
    }

    function it_should_compute_key_back_using_base64()
    {
        global $iteratorToArray;
        $iteratorToArray = array(sys_get_temp_dir().'/l3l0/'.base64_encode('filename'), sys_get_temp_dir().'/l3l0/'.base64_encode('filename1'), sys_get_temp_dir().'/l3l0/'.base64_encode('aaa/filename'));

        $this->keys()->shouldReturn(array('aaa', 'aaa/filename', 'filename', 'filename1'));
    }
}
