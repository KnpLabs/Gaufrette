<?php

namespace spec\Gaufrette;

use PHPSpec2\ObjectBehavior;

class File extends ObjectBehavior
{
    /**
     * @param \Gaufrette\Filesystem $filesystem
     */
    function let($filesystem)
    {
        $this->beConstructedWith('filename', $filesystem);
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\File');
    }

    function it_should_gives_access_to_key()
    {
        $this->getKey()->shouldReturn('filename');
    }

    /**
     * @param \Gaufrette\Filesystem $filesystem
     */
    function it_should_get_content($filesystem)
    {
        $filesystem->read('filename')->shouldBeCalled()->willReturn('Some content');

        $this->getContent()->shouldReturn('Some content');
    }

    /**
     * @param \Gaufrette\Filesystem $filesystem
     * @param \spec\Gaufrette\MetadataAdapter $adapter
     */
    function it_should_get_metadata_when_supports_its($filesystem, $adapter)
    {
        $metadata = array('id' => '123');
        $adapter->getMetadata('filename')->shouldBeCalled()->willReturn($metadata);
        $filesystem->getAdapter()->willReturn($adapter);

        $this->getMetadata()->shouldReturn($metadata);
    }

    function it_should_not_get_metadata_by_default()
    {
        $this->getMetadata()->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Filesystem $filesystem
     * @param \spec\Gaufrette\MetadataAdapter $adapter
     */
    function it_should_set_metadata_when_supports_its($filesystem, $adapter)
    {
        $metadata = array('id' => '123');
        $adapter->setMetadata('filename', $metadata)->shouldBeCalled();
        $filesystem->getAdapter()->willReturn($adapter);

        $this->setMetadata($metadata)->shouldReturn(true);
        $this->getMetadata()->shouldReturn($metadata);
    }

    function it_should_not_set_metadata_by_default()
    {
        $this->setMetadata(array('id' => '123'))->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Filesystem $filesystem
     */
    function it_should_set_content($filesystem)
    {
        $filesystem->write('filename', 'some content', true)->shouldBeCalled()->willReturn(21);

        $this->setContent('some content')->shouldReturn(21);
        $this->getContent('filename')->shouldReturn('some content');
    }

    function it_should_set_name()
    {
        $this->setName('name');
        $this->getName()->shouldReturn('name');
    }

    function it_should_set_created_date()
    {
        $dateTime = new \DateTime();

        $this->setCreated($dateTime);
        $this->getCreated()->shouldBe($dateTime);
    }

    function it_should_set_size()
    {
        $this->setSize(12);
        $this->getSize()->shouldReturn(12);
    }

    /**
     * @param \Gaufrette\Filesystem $filesystem
     */
    function it_should_check_exists_in_filesystem($filesystem)
    {
        $filesystem->has('filename')->willReturn(true);
        $this->exists()->shouldReturn(true);

        $filesystem->has('filename')->willReturn(false);
        $this->exists()->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Filesystem $filesystem
     */
    function it_should_delete_file_from_filesystem($filesystem)
    {
        $filesystem->delete('filename')->shouldBeCalled()->willReturn(true);
        $this->delete()->shouldReturn(true);
    }
}

interface MetadataAdapter extends \Gaufrette\Adapter,
                                  \Gaufrette\Adapter\MetadataSupporter
{}
