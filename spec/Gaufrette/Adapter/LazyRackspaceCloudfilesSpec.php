<?php

namespace spec\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LazyRackspaceCloudfilesSpec extends ObjectBehavior
{
    /**
     * @param \Gaufrette\Adapter\RackspaceCloudfiles\ConnectionFactoryInterface $connectionFactory
     * @param \CF_Connection $connection
     */
    function let($connectionFactory, $connection)
    {
        $connectionFactory->create()->willReturn($connection);
        $this->beConstructedWith($connectionFactory, 'my_container');
    }

    function it_is_rackspace_adapter()
    {
        $this->shouldHaveType('\Gaufrette\Adapter\RackspaceCloudfiles');
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_lazily_fetches_container_before_read($connection, $container, $object)
    {
        $connection->get_container('my_container')->willReturn($container)->shouldBeCalledTimes(1);
        $connection->create_container(Argument::cetera())->shouldNotBeCalled();
        $object->read()->willReturn('some content');
        $container->get_object('filename')->willReturn($object);
        $container->get_object('filename1')->willReturn($object);

        $this->read('filename')->shouldReturn('some content');

        //get_container() should not be called second time
        $this->read('filename1')->shouldReturn('some content');
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_lazily_creates_container_before_read($connectionFactory, $connection, $container, $object)
    {
        $this->beConstructedWith($connectionFactory, 'my_container', true);

        $connection->get_container(Argument::any())->shouldNotBeCalled();
        $connection->create_container('my_container')->willReturn($container)->shouldBeCalled();
        $object->read()->willReturn('some content');
        $container->get_object('filename')->willReturn($object);

        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_lazily_fetches_container_before_write($connection, $container, $object)
    {
        $connection->get_container('my_container')->willReturn($container)->shouldBeCalled();
        $object
            ->write('some content')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $container
            ->get_object('filename')
            ->shouldBeCalled()
            ->willReturn($object)
        ;

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_lazily_fetches_container_before_exists($connection, $container, $object)
    {
        $connection->get_container('my_container')->willReturn($container)->shouldBeCalled();
        $container
            ->get_object('filename')
            ->willReturn($object)
        ;

        $this->exists('filename')->shouldReturn(true);
    }

    /**
     * @param \CF_Container $container
     */
    function it_lazily_fetches_container_before_keys($connection, $container)
    {
        $connection->get_container('my_container')->willReturn($container)->shouldBeCalled();
        $container->list_objects(0, null, null)->willReturn(array('filename2', 'filename1'));

        $this->keys()->shouldReturn(array('filename1', 'filename2'));
    }

    /**
     * @param \CF_Container $container
     * @param \CF_Object $object
     */
    function it_lazily_fetches_container_before_checksum($connection, $container, $object)
    {
        $connection->get_container('my_container')->willReturn($container)->shouldBeCalled();

        $object->getETag()->willReturn('123m5');
        $container->get_object('filename')->willReturn($object);

        $this->checksum('filename')->shouldReturn('123m5');
    }

    /**
     * @param \CF_Container $container
     */
    function it_lazily_fetches_container_before_delete($connection, $container)
    {
        $connection->get_container('my_container')->willReturn($container)->shouldBeCalled();
        $container->delete_object('filename')->shouldBeCalled();

        $this->delete('filename')->shouldReturn(true);
    }
}
