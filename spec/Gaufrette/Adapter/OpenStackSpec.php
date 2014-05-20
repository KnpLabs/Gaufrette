<?php

namespace spec\Gaufrette\Adapter;

use Guzzle\Http\Exception\BadResponseException;
use OpenStack\Common\Exception;
use OpenStack\Common\Transport\Exception\FileNotFoundException;
use OpenStack\ObjectStore\v1\Resource\Object;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use PhpSpec\Exception\Example\SkippingException;

/**
 * OpenStackSpec
 *
 * @author  Gaultier Boniface <gboniface@wysow.fr>
 */
class OpenStackSpec extends ObjectBehavior
{
    /**
     * @param OpenStack\ObjectStore\v1\ObjectStorage $objectStore
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     */
    function let($objectStore, $container)
    {
        $this->verifyRequirements();

        $objectStore->container("test")->willReturn($container);
        $this->beConstructedWith($objectStore, 'test', false);
    }

    function it_is_adapter()
    {
        $this->verifyRequirements();

        $this->shouldHaveType('Gaufrette\Adapter');
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     * @param OpenStack\ObjectStore\v1\Resource\Object $object
     */
    function it_reads_file($container, $object)
    {
        $this->verifyRequirements();

        $object->content()->willReturn("Hello World");
        $container->proxyObject("test")->willReturn($object);

        $this->read('test')->shouldReturn('Hello World');
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     */
    function it_reads_file_on_error_returns_null($container)
    {
        $this->verifyRequirements();

        $container->proxyObject("test")->willThrow(new Exception());

        $this->read('test')->shouldReturn(null);
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     * @param OpenStack\ObjectStore\v1\Resource\Object $object
     */
    function it_writes_file_returns_size($container, $object)
    {
        $this->verifyRequirements();

        $testData     = "Hello World!";
        $testDataSize = strlen($testData);

        $object->setContent($testData)->willReturn($object);
        $object->setContentType('text/plain')->willReturn($object);
        $object->contentLength()->willReturn($testDataSize);
        $container->proxyObject(Argument::cetera())->willReturn($object);
        $container->save($object)->willReturn(true);

        $this->write('test', $testData)->shouldReturn($testDataSize);
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     * @param OpenStack\ObjectStore\v1\Resource\Object $object
     */
    function it_writes_file_and_write_fails_returns_false($container, $object)
    {
        $this->verifyRequirements();

        $testData = "Hello World!";

        $object->setContent($testData)->willReturn($object);
        $object->setContentType('text/plain')->willReturn($object);
        $object->contentLength()->willThrow(new Exception());
        $container->proxyObject(Argument::cetera())->willReturn($object);
        $container->save($object)->willThrow(new Exception());

        $this->write('test', $testData)->shouldReturn(false);
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     * @param OpenStack\ObjectStore\v1\Resource\Object $object
     */
    function it_returns_true_if_key_exists($container, $object)
    {
        $this->verifyRequirements();

        $container->proxyObject('test')->willReturn($object);

        $this->exists('test')->shouldReturn(true);
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     */
    function it_returns_false_if_key_does_not_exist($container)
    {
        $this->verifyRequirements();

        $container->proxyObject('test')->willThrow(new Exception());

        $this->exists('test')->shouldReturn(false);
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     * @param OpenStack\ObjectStore\v1\Resource\Object $object
     */
    function it_deletes_file_on_success_returns_true($container, $object)
    {
        $this->verifyRequirements();

        $container->delete("test")->willReturn(true);
        $container->proxyObject("test")->willReturn($object);

        $this->delete('test')->shouldReturn(true);
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     * @param OpenStack\ObjectStore\v1\Resource\Object $object
     */
    function it_deletes_file_returns_false_on_failure($container, $object)
    {
        $this->verifyRequirements();

        $container->delete("test")->willThrow(new Exception());
        $container->proxyObject("test")->willReturn($object);

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     */
    function it_deletes_file_if_file_does_not_exist_returns_false($container)
    {
        $this->verifyRequirements();

        $container->proxyObject("test")->willThrow(new FileNotFoundException());

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     * @param OpenStack\ObjectStore\v1\Resource\Object $object
     */
    function it_returns_checksum_if_file_exists($container, $object)
    {
        $this->verifyRequirements();

        $object->eTag()->willReturn("test String");
        $container->proxyObject("test")->willReturn($object);

        $this->checksum('test')->shouldReturn("test String");
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     * @param OpenStack\ObjectStore\v1\Resource\Object $object
     */
    function it_returns_content_type_if_file_exists($container, $object)
    {
        $this->verifyRequirements();

        $object->contentType()->willReturn("application/octet-stream");
        $container->proxyObject("test")->willReturn($object);

        $this->contentType('test')->shouldReturn("application/octet-stream");
    }

    /**
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     * @param OpenStack\ObjectStore\v1\Resource\Object $object
     */
    function it_returns_false_when_file_does_not_exist($container)
    {
        $this->verifyRequirements();

        $container->proxyObject("test")->willThrow(new FileNotFoundException());

        $this->checksum('test')->shouldReturn(false);
    }

    /**
     * @param OpenStack\ObjectStore\v1\ObjectStorage $objectStore
     */
    function it_throws_exception_if_container_does_not_exist($objectStore)
    {
        $this->verifyRequirements();

        $containerName = 'container-does-not-exist';

        $objectStore->container($containerName)->willThrow(new Exception());
        $this->beConstructedWith($objectStore, $containerName);

        $this->shouldThrow('\OpenStack\Common\Exception')->duringExists('test');
    }

    /**
     * @param OpenStack\ObjectStore\v1\ObjectStorage $objectStore
     * @param OpenStack\ObjectStore\v1\Resource\Container $container
     */
    function it_creates_container($objectStore, $container)
    {
        $this->verifyRequirements();

        $containerName = 'container-does-not-yet-exist';
        $filename = 'test';

        $objectStore->container($containerName)->willThrow(new Exception());
        $objectStore->createContainer($containerName)->willReturn(true);
        $objectStore->container($containerName)->willReturn($container);
        $container->proxyObject($filename)->willThrow(new FileNotFoundException());

        $this->beConstructedWith($objectStore, $containerName, true);

        $this->exists($filename)->shouldReturn(false);
    }

    /**
     * @param OpenStack\ObjectStore\v1\ObjectStorage $objectStore
     */
    function it_throws_exception_if_container_creation_fails($objectStore)
    {
        $this->verifyRequirements();

        $containerName = 'container-does-not-yet-exist';

        $objectStore->container($containerName)->willThrow(new Exception());
        $objectStore->createContainer($containerName)->willReturn(false);

        $this->beConstructedWith($objectStore, $containerName, true);

        $this->shouldThrow('\OpenStack\Common\Exception')->duringExists('test');
    }

    private function verifyRequirements()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            throw new SkippingException('Undoable stuff requires php 5.4.0');
        }
    }
}
