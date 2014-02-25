<?php
namespace spec\Gaufrette\Adapter;


use OpenCloud\ObjectStore\Exception\ObjectNotFoundException;
use PhpSpec\ObjectBehavior;
use OpenCloud\Common\Exceptions\ObjFetchError;
use OpenCloud\Common\Exceptions\CreateUpdateError;
use OpenCloud\Common\Exceptions\DeleteError;
use Prophecy\Argument;

/**
 * Class LazyOpenStackCloudFilesSpec
 * @package spec\Gaufrette\Adapter
 * @author  Chris Warner <cdw.lighting@gmail.com>
 */
class LazyOpenStackCloudFilesSpec extends ObjectBehavior
{

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     */
    function let($connectionFactory)
    {
        $containerName     = 'test';
        $createContainer   = false;

        $this->beConstructedWith($connectionFactory, $containerName, $createContainer);
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack $openStack
     * @param \OpenCloud\ObjectStore\Service $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container $container
     */
    function it_initializes_object_store($connectionFactory, $openStack, $objectStore, $container)
    {
        $connectionFactory->create()->shouldBeCalled()->willReturn($openStack);
        $openStack->objectStoreService(Argument::cetera())->willReturn($objectStore);
        $objectStore->getContainer(Argument::any())->shouldBeCalled()->willReturn($container);
        $container->getObject(Argument::any())->willThrow(new ObjectNotFoundException());

        $this->exists("test-file-name");
    }
}
