<?php

namespace spec\Gaufrette\Adapter;

require_once __DIR__.'/../../../vendor/rackspace/php-cloudfiles/cloudfiles.php';

use PHPSpec2\ObjectBehavior;

class RackspaceCloudfiles extends ObjectBehavior
{
    /**
     * @param \CF_Container $container
     */
    function let($container)
    {
        $this->beConstructedWith($container);
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\RackspaceCloudfiles');
        $this->shouldHaveType('Gaufrette\Adapter');
        $this->shouldHaveType('Gaufrette\Adapter\ChecksumCalculator');
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_should_read_file($container, $object)
    {
        $object->read()->willReturn('some content');
        $container->get_object('filename')->willReturn($object);

        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_should_not_mask_exception_when_read($container, $object)
    {
        $object->read()->willThrow(new \RuntimeException('read'));
        $container->get_object('filename')->willReturn($object);

        $this->shouldThrow(new \RuntimeException('read'))->duringRead('filename');
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_should_check_if_file_exists($container, $object)
    {
        $container
            ->get_object('filename')
            ->willReturn($object);
        $container
            ->get_object('filename2')
            ->willReturn(false);
        $container
            ->get_object('filename3')
            ->willThrow(new \NoSuchObjectException);

        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename2')->shouldReturn(false);
        $this->exists('filename3')->shouldReturn(false);
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_should_not_mask_exception_when_check_if_exists($container, $object)
    {
        $container->get_object('filename')->willThrow(new \RuntimeException('exists'));

        $this->shouldThrow(new \RuntimeException('exists'))->duringRead('filename');
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_should_write_file($container, $object)
    {
        $object
            ->write('some content')
            ->shouldBeCalled()
            ->willReturn(true);
        $container
            ->get_object('filename')
            ->shouldBeCalled()
            ->willReturn($object);

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_should_not_write_file($container, $object)
    {
        $object
            ->write('some content')
            ->shouldBeCalled()
            ->willReturn(false);
        $container
            ->get_object('filename')
            ->shouldBeCalled()
            ->willReturn($object);

        $this->write('filename', 'some content')->shouldReturn(false);
    }

    /**
     * @param \CF_Container $container
     */
    function it_should_not_mask_exception_when_write($container)
    {
        $container->get_object('filename')->willThrow(new \RuntimeException('write'));

        $this->shouldThrow(new \RuntimeException('write'))->duringWrite('filename', 'some content');
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_should_create_object_when_write($container, $object)
    {
        $object
            ->write('some content')
            ->shouldBeCalled()
            ->willReturn(true);
        $container
            ->get_object('filename')
            ->shouldBeCalled()
            ->willReturn(false);
        $container
            ->create_object('filename')
            ->shouldBeCalled()
            ->willReturn($object);

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $fromObject
     * @param \CF_Object $toObject
     */
    function it_should_rename_file($container, $fromObject, $toObject)
    {
        $fromObject
            ->read()
            ->willReturn('some content');
        $toObject
            ->write('some content')
            ->willReturn(true);
        $container
            ->get_object('filename')
            ->willReturn($fromObject);
        $container
            ->get_object('filename1')
            ->willReturn($toObject);

        $this->rename('filename', 'filename1')->shouldReturn(true);
    }

    /**
     * @param \CF_Container $container
     */
    function it_should_not_mask_exception_when_rename($container)
    {
        $container->get_object('filename')->willThrow(new \RuntimeException('rename'));

        $this->shouldThrow(new \RuntimeException('rename'))->duringRename('filename', 'fromFilename');
    }

    /**
     * @param \CF_Container $container
     */
    function it_should_get_keys($container)
    {
        $container->list_objects(0, null, null)->willReturn(array('filename2', 'filename1'));

        $this->keys()->shouldReturn(array('filename1', 'filename2'));
    }

    /**
     * @param \CF_Container $container
     */
    function it_should_not_mask_exception_when_get_keys($container)
    {
        $container->list_objects(0, null, null)->willThrow(new \RuntimeException('keys'));

        $this->shouldThrow(new \RuntimeException('keys'))->duringKeys();
    }

    function it_should_not_support_mtime()
    {
        $this->mtime('filename')->shouldBe(false);
        $this->mtime('filename2')->shouldBe(false);
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_should_calculate_checksum($container, $object)
    {
        $object->getETag()->willReturn('123m5');
        $container->get_object('filename')->willReturn($object);

        $this->checksum('filename')->shouldReturn('123m5');
    }

    /**
     * @param \CF_Container $container
     */
    function it_should_not_mask_exception_when_calculate_checksum($container)
    {
        $container->get_object('filename')->willThrow(new \RuntimeException('checksum'));

        $this->shouldThrow(new \RuntimeException('checksum'))->duringChecksum('filename');
    }

    /**
     * @param \CF_Container $container
     */
    function it_should_delete_object($container)
    {
        $container->delete_object('filename')->shouldBeCalled();

        $this->delete('filename')->shouldReturn(true);
    }

    /**
     * @param \CF_Container $container
     */
    function it_should_not_delete_object($container)
    {
        $container->delete_object('filename')->willThrow(new \NoSuchObjectException);

        $this->delete('filename')->shouldReturn(false);
    }

    /**
     * @param \CF_Container $container
     */
    function it_should_not_mask_exception_when_delete($container)
    {
        $container->delete_object('filename')->willThrow(new \RuntimeException('delete'));

        $this->shouldThrow(new \RuntimeException('delete'))->duringDelete('filename');
    }

    function it_does_not_support_directory()
    {
        $this->isDirectory('filename')->shouldReturn(false);
    }
}
