<?php

namespace spec\Gaufrette;

use Gaufrette\Filesystem;
use PhpSpec\ObjectBehavior;

class FilesystemMapSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(\Gaufrette\FilesystemMap::class);
    }

    public function it_checks_if_has_mapped_filesystem(Filesystem $filesystem): void
    {
        $this->set('some', $filesystem);
        $this->has('some')->shouldReturn(true);
        $this->has('other')->shouldReturn(false);
    }

    public function it_sets_mapped_filesystem(Filesystem $filesystem): void
    {
        $this->set('some', $filesystem);
        $this->get('some')->shouldReturn($filesystem);
    }

    public function it_fails_when_get_filesystem_which_was_not_mapped(): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('There is no filesystem defined having "some" name.'))
            ->duringGet('some')
        ;
    }

    public function it_removes_mapped_filesystem(Filesystem $filesystem): void
    {
        $this->set('some', $filesystem);
        $this->remove('some');

        $this->has('some')->shouldReturn(false);
    }

    public function it_fails_when_try_to_remove_filesystem_which_was_not_mapped(): void
    {
        $this
            ->shouldThrow(new \InvalidArgumentException('Cannot remove the "some" filesystem as it is not defined.'))
            ->duringRemove('some')
        ;
    }

    public function it_removes_all_filesystems(Filesystem $filesystem): void
    {
        $this->set('some', $filesystem);
        $this->set('other', $filesystem);
        $this->clear();

        $this->has('some')->shouldReturn(false);
        $this->has('other')->shouldReturn(false);
        $this->all()->shouldReturn([]);
    }
}
