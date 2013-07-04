<?php

namespace spec\Gaufrette\Util;

use PHPSpec2\ObjectBehavior;

class Checksum extends ObjectBehavior
{
    function let()
    {
        $path = __DIR__.DIRECTORY_SEPARATOR.'testFile';
        file_put_contents($path, 'some other content');
    }

    function it_should_calculate_checksum_from_content()
    {
        $this->fromContent('some content')->shouldReturn(md5('some content'));
    }

    function it_should_calculate_checksum_from_filepath()
    {
        $path = __DIR__.DIRECTORY_SEPARATOR.'testFile';
        $this->fromFile($path)->shouldReturn(md5('some other content'));
    }

    function letgo()
    {
        $path = __DIR__.DIRECTORY_SEPARATOR.'testFile';
        @unlink(__DIR__.DIRECTORY_SEPARATOR.'testFile');
    }
}
