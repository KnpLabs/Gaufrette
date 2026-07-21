<?php

namespace spec\Gaufrette\Util;

use PhpSpec\ObjectBehavior;

class ChecksumSpec extends ObjectBehavior
{
    public function let(): void
    {
        file_put_contents($this->getTestFilePath(), 'some other content');
    }

    public function letGo(): void
    {
        @unlink($this->getTestFilePath());
    }

    public function it_calculates_checksum_from_content(): void
    {
        $this->fromContent('some content')
            ->shouldReturn(md5('some content'))
        ;
    }

    public function it_calculates_checksum_from_filepath(): void
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
