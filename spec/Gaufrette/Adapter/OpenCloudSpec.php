<?php

namespace spec\Gaufrette\Adapter;

use Guzzle\Http\Exception\BadResponseException;
use OpenCloud\Common\Collection;
use OpenCloud\Common\Exceptions\CreateUpdateError;
use OpenCloud\Common\Exceptions\DeleteError;
use OpenCloud\ObjectStore\Exception\ObjectNotFoundException;
use OpenCloud\ObjectStore\Resource\Container;
use OpenCloud\ObjectStore\Resource\DataObject;
use OpenCloud\ObjectStore\Service;
use PhpSpec\ObjectBehavior;

/**
 * OpenCloudSpec
 *
 * @author  Chris Warner <cdw.lighting@gmail.com>
 * @author  Daniel Richter <nexyz9@gmail.com>
 */
class OpenCloudSpec extends ObjectBehavior
{
    /**
     * @param OpenCloud\ObjectStore\Service $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container $container
     */
    function let(Service $objectStore, Container $container)
    {
        $objectStore->getContainer('test')->willReturn($container);
        $this->beConstructedWith($objectStore, 'test', false);
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_reads_file(Container $container, DataObject $object)
    {
        $object->getContent()->willReturn('Hello World');
        $container->getObject('test')->willReturn($object);

        $this->read('test')->shouldReturn('Hello World');
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container $container
     */
    function it_reads_file_on_error_returns_false(Container $container)
    {
        $container->getObject('test')->willThrow(new ObjectNotFoundException());

        $this->read('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_writes_file_returns_size(Container $container, DataObject $object)
    {
        $testData = 'Hello World!';
        $testDataSize = strlen($testData);

        $object->getContentLength()->willReturn($testDataSize);
        $container->uploadObject('test', $testData)->willReturn($object);

        $this->write('test', $testData)->shouldReturn($testDataSize);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     */
    function it_writes_file_and_write_fails_returns_false(Container $container)
    {
        $testData = 'Hello World!';

        $container->uploadObject('test', $testData)->willThrow(new CreateUpdateError());

        $this->write('test', $testData)->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_returns_true_if_key_exists(Container $container, DataObject $object)
    {
        $container->getPartialObject('test')->willReturn($object);

        $this->exists('test')->shouldReturn(true);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     */
    function it_returns_false_if_key_does_not_exist(Container $container)
    {
        $container->getPartialObject('test')->willThrow(new BadResponseException());

        $this->exists('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_deletes_file_on_success_returns_true(Container $container, DataObject $object)
    {
        $object->delete()->willReturn(null);
        $container->getObject('test')->willReturn($object);

        $this->delete('test')->shouldReturn(true);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_deletes_file_returns_false_on_failure(Container $container, DataObject $object)
    {
        $object->delete()->willThrow(new DeleteError());
        $container->getObject('test')->willReturn($object);

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container $container
     */
    function it_deletes_file_if_file_does_not_exist_returns_false(Container $container)
    {
        $container->getObject('test')->willThrow(new ObjectNotFoundException());

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_returns_checksum_if_file_exists(Container $container, DataObject $object)
    {
        $object->getEtag()->willReturn('test String');
        $container->getObject('test')->willReturn($object);

        $this->checksum('test')->shouldReturn('test String');
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container $container
     */
    function it_returns_false_when_file_does_not_exist(Container $container)
    {
        $container->getObject('test')->willThrow(new ObjectNotFoundException());

        $this->checksum('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container $container
     * @param OpenCloud\Common\Collection              $objectList
     * @param OpenCloud\ObjectStore\Resource\DataObject $object1
     * @param OpenCloud\ObjectStore\Resource\DataObject $object2
     * @param OpenCloud\ObjectStore\Resource\DataObject $object3
     */
    function it_returns_files_as_sorted_array(Container $container, Collection $objectList, DataObject $object1, DataObject $object2, DataObject $object3)
    {
        $outputArray = ['key1', 'key2', 'key5'];
        $index = 0;

        $object1->getName()->willReturn('key5');
        $object2->getName()->willReturn('key2');
        $object3->getName()->willReturn('key1');

        $objects = [$object1, $object2, $object3];

        $objectList->next()->will(
                   function () use ($objects, &$index) {
                       if ($index < count($objects)) {
                           $index++;

                           return $objects[$index - 1];
                       }
                   }
        )          ->shouldBeCalledTimes(count($objects) + 1);

        $container->objectList()->willReturn($objectList);

        $this->keys()->shouldReturn($outputArray);
    }

    /**
     * @param OpenCloud\ObjectStore\Service $objectStore
     */
    function it_throws_exception_if_container_does_not_exist(Service $objectStore)
    {
        $containerName = 'container-does-not-exist';

        $objectStore->getContainer($containerName)->willThrow(new BadResponseException());
        $this->beConstructedWith($objectStore, $containerName);

        $this->shouldThrow('\RuntimeException')->duringExists('test');
    }

    /**
     * @param OpenCloud\ObjectStore\Service $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     */
    function it_creates_container(Service $objectStore, Container $container)
    {
        $containerName = 'container-does-not-yet-exist';
        $filename = 'test';

        $objectStore->getContainer($containerName)->willThrow(new BadResponseException());
        $objectStore->createContainer($containerName)->willReturn($container);
        $container->getPartialObject($filename)->willThrow(new BadResponseException());

        $this->beConstructedWith($objectStore, $containerName, true);

        $this->exists($filename)->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Service $objectStore
     */
    function it_throws_exeption_if_container_creation_fails(Service $objectStore)
    {
        $containerName = 'container-does-not-yet-exist';

        $objectStore->getContainer($containerName)->willThrow(new BadResponseException());
        $objectStore->createContainer($containerName)->willReturn(false);

        $this->beConstructedWith($objectStore, $containerName, true);

        $this->shouldThrow('\RuntimeException')->duringExists('test');
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     */
    function it_returns_false_if_the_object_does_not_exists_when_fetching_mtime(Container $container)
    {
        $container->getObject('foo')->willThrow(ObjectNotFoundException::class);

        $this->mtime('foo')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     */
    function it_fetches_file_mtime(DataObject $object, Container $container)
    {
        $container->getObject('foo')->willReturn($object);
        $object->getLastModified()->willReturn('Tue, 13 Jun 2017 22:02:34 GMT');

        $this->mtime('foo')->shouldReturn('1497391354');
    }
}
