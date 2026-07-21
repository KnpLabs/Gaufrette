<?php

namespace spec\Gaufrette;

use PhpSpec\ObjectBehavior;

class StreamModeSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->beConstructedWith('r');
        $this->shouldHaveType(\Gaufrette\StreamMode::class);
    }

    public function it_gives_access_to_mode(): void
    {
        $this->beConstructedWith('r+');
        $this->getMode()->shouldReturn('r+');
    }

    public function it_allows_write_only(): void
    {
        $this->beConstructedWith('w');

        $this->allowsWrite()->shouldReturn(true);
        $this->allowsRead()->shouldReturn(false);
    }

    public function it_allows_write_and_read(): void
    {
        $this->beConstructedWith('w+');

        $this->allowsWrite()->shouldReturn(true);
        $this->allowsRead()->shouldReturn(true);
    }

    public function it_allows_read_only(): void
    {
        $this->beConstructedWith('r');

        $this->allowsWrite()->shouldReturn(false);
        $this->allowsRead()->shouldReturn(true);
    }

    public function it_allows_to_existing_file_opening(): void
    {
        $this->beConstructedWith('r');

        $this->allowsExistingFileOpening()->shouldReturn(true);
    }

    public function it_does_not_allow_to_existing_file_opening(): void
    {
        $this->beConstructedWith('x');

        $this->allowsExistingFileOpening()->shouldReturn(false);
    }

    public function it_allows_new_file_opening(): void
    {
        $this->beConstructedWith('w');

        $this->allowsNewFileOpening()->shouldReturn(true);
    }

    public function it_does_not_allow_new_file_opening(): void
    {
        $this->beConstructedWith('r');

        $this->allowsNewFileOpening()->shouldReturn(false);
    }

    public function it_implies_existing_content_deletion(): void
    {
        $this->beConstructedWith('w+');

        $this->allowsNewFileOpening()->shouldReturn(true);
    }

    public function it_does_not_implies_existing_content_deletion(): void
    {
        $this->beConstructedWith('r+');

        $this->allowsNewFileOpening()->shouldReturn(false);
    }

    public function it_implies_positioning_cursor_at_the_beginning(): void
    {
        $this->beConstructedWith('r+');

        $this->impliesPositioningCursorAtTheBeginning()->shouldReturn(true);
    }

    public function it_does_no_implies_positioning_cursor_at_the_beginning(): void
    {
        $this->beConstructedWith('a');

        $this->impliesPositioningCursorAtTheBeginning()->shouldReturn(false);
    }

    public function it_implies_positioning_cursor_at_the_end(): void
    {
        $this->beConstructedWith('a');

        $this->impliesPositioningCursorAtTheEnd()->shouldReturn(true);
    }

    public function it_does_no_implies_positioning_cursor_at_the_end(): void
    {
        $this->beConstructedWith('w');

        $this->impliesPositioningCursorAtTheEnd()->shouldReturn(false);
    }

    public function it_should_be_binary(): void
    {
        $this->beConstructedWith('wb+');

        $this->isBinary()->shouldReturn(true);
    }

    public function it_should_not_be_binary(): void
    {
        $this->beConstructedWith('w+');

        $this->isBinary()->shouldReturn(false);
    }

    public function it_should_not_be_text(): void
    {
        $this->beConstructedWith('wb+');

        $this->isText()->shouldReturn(false);
    }

    public function it_should_be_text(): void
    {
        $this->beConstructedWith('w+');

        $this->isText()->shouldReturn(true);
    }
}
