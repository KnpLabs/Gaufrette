<?php

namespace spec\Gaufrette\Util;

use PhpSpec\ObjectBehavior;

class ChecksumSpec extends ObjectBehavior
{
    function let()
    {
        file_put_contents($this->getTestFilePath(), 'some other content');
    }

    function letGo()
    {
        @unlink($this->getTestFilePath());
    }

    function it_calculates_checksum_from_content()
    {
        $this->fromContent('some content')
            ->shouldReturn(md5('some content'))
        ;
    }

    function it_calculates_checksum_from_filepath()
    {
        $this->fromFile($this->getTestFilePath())
            ->shouldReturn(md5('some other content'))
        ;
    }

    private function getTestFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'testFile';
    }
}
