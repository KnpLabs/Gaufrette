<?php

namespace spec\Gaufrette\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\BufferStream;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\Identity\v2\Api;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use OpenStack\ObjectStore\v1\Service;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\StreamInterface;

/**
 * OpenStackSpec
 *
 * @author  Chris Warner <cdw.lighting@gmail.com>
 * @author  Daniel Richter <nexyz9@gmail.com>
 * @author  Nicolas MURE <nicolas.mure@knplabs.com>
 */
class OpenStackSpec extends ObjectBehavior
{
    function let(Service $objectStore, Container $container)
    {
        $objectStore->containerExists('test')->willReturn(true);
        $objectStore->getContainer('test')->willReturn($container);
        $this->beConstructedWith($objectStore, 'test', false);
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_throws_exception_when_not_able_to_determine_if_container_exist(Service $objectStore)
    {
        $containerName = 'container-does-not-exist';

        $objectStore->containerExists($containerName)->willThrow($this->getBadResponseError(400));
        $this->beConstructedWith($objectStore, $containerName);

        $this->shouldThrow('\RuntimeException')->duringExists('test');
    }

    function it_throws_exception_if_container_does_not_exist(Service $objectStore)
    {
        $containerName = 'container-does-not-exist';

        $objectStore->containerExists($containerName)->willReturn(false);
        $this->beConstructedWith($objectStore, $containerName);

        $this->shouldThrow('\RuntimeException')->duringExists('test');
    }

    function it_reads_file(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->download()->willReturn($this->getReadableStream('Hello World!'));

        $this->read('test')->shouldReturn('Hello World!');
    }

    function it_throws_file_not_found_while_reading_unexisting_file(Container $container)
    {
        $container->getObject('test')->willThrow($this->getBadResponseError(404));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringread('test');
    }

    function it_throws_storage_failure_while_reading(Container $container)
    {
        $container->getObject('test')->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringread('test');
    }

    function it_writes_file(Container $container)
    {
        $container->createObject([
            'name' => 'test',
            'content' => 'Hello World!',
        ])->shouldBeCalled();

        $this->write('test', 'Hello World!')->shouldNotThrow();
    }

    function it_throws_storage_failure_while_writing(Container $container)
    {
        $container->createObject([
            'name' => 'test',
            'content' => 'Hello World!',
        ])->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringwrite('test', 'Hello World!');
    }

    function it_returns_true_if_key_exists(Container $container)
    {
        $container->objectExists('test')->willReturn(true);

        $this->exists('test')->shouldReturn(true);
    }

    function it_returns_false_if_key_does_not_exist(Container $container)
    {
        $container->objectExists('test')->willReturn(false);

        $this->exists('test')->shouldReturn(false);
    }

    function it_throws_storage_failure_while_checking_if_file_exists(Container $container)
    {
        $container->objectExists('test')->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringexists('test');
    }

    function it_lists_objects(Container $container)
    {
        $client = new Client();
        $api = new Api();

        $generate = function () use ($client, $api) {
            for ($i = 0; $i < 3; $i++) {
                $object = new StorageObject($client, $api);
                $object->name = sprintf('object %d', $i + 1);

                yield $object;
            }
        };

        $container->listObjects()->willReturn($generate());

        $this->keys()->shouldReturn([
            'object 1',
            'object 2',
            'object 3',
        ]);
    }

    function it_throws_storage_failure_while_listing_objects(Container $container)
    {
        $container->listObjects()->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringkeys();
    }

    function it_lists_objects_with_prefix(Container $container)
    {
        $client = new Client();
        $api = new Api();

        $generate = function () use ($client, $api) {
            for ($i = 0; $i < 6; $i++) {
                $object = new StorageObject($client, $api);
                $object->name = sprintf('%sobject %d', $i < 3 ? 'prefixed ' : '', $i + 1);

                yield $object;
            }
        };

        $container->listObjects()->willReturn($generate());

        $this->listKeys('prefix')->shouldReturn([
            'prefixed object 1',
            'prefixed object 2',
            'prefixed object 3',
        ]);
    }

    function it_throws_storage_failure_while_listing_objects_with_prefix(Container $container)
    {
        $container->listObjects()->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringlistKeys('prefix');
    }

    function it_fetches_mtime(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->shouldBeCalled();
        $object->lastModified = 'Tue, 13 Jun 2017 22:02:34 GMT';

        $this->mtime('test')->shouldReturn('1497391354');
    }

    function it_throws_file_not_found_exception_when_trying_to_fetch_the_mtime_of_an_unexisting_file(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->willThrow($this->getBadResponseError(404));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringmtime('test');
    }

    function it_throws_storage_failure_while_fetching_mtime(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringmtime('test');
    }

    function it_deletes_file(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->delete()->shouldBeCalled();

        $this->delete('test')->shouldNotThrow();
    }

    function it_throws_file_not_found_exception_when_trying_to_delete_an_unexisting_file(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->delete()->willThrow($this->getBadResponseError(404));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringdelete('test');
    }

    function it_throws_storage_failure_while_deleting(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->delete()->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringdelete('test');
    }

    function it_renames_file(Container $container, StorageObject $source, StorageObject $dest)
    {
        $container->objectExists('source')->willReturn(true);
        $container->objectExists('dest')->willReturn(false);

        $container->getObject('source')->willReturn($source);
        $container->getObject('dest')->willReturn($dest);
        $source->download()->willReturn($this->getReadableStream('Hello World!'));
        $source->getMetadata()->willReturn(['meta' => 'data']);
        $dest->resetMetadata(['meta' => 'data'])->shouldBeCalled();

        $container->createObject([
            'name' => 'dest',
            'content' => 'Hello World!',
        ])->shouldBeCalled();

        $source->delete()->shouldBeCalled();

        $this->rename('source', 'dest')->shouldNotThrow();
    }

    function it_throws_file_not_found_exception_when_source_does_not_exist_during_rename(Container $container)
    {
        $container->objectExists('source')->willReturn(false);

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringrename('source', 'dest');
    }

    function it_throws_file_already_exists_when_dest_already_exists_during_rename(Container $container)
    {
        $container->objectExists('source')->willReturn(true);
        $container->objectExists('dest')->willReturn(true);

        $this->shouldThrow('Gaufrette\Exception\FileAlreadyExists')->duringrename('source', 'dest');
    }

    function it_does_not_handle_directories()
    {
        $this->isDirectory('whatever')->shouldReturn(false);
    }

    function it_returns_checksum(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->shouldBeCalled();
        $object->hash = '1234abcd';

        $this->checksum('test')->shouldReturn('1234abcd');
    }

    function it_throws_file_not_found_exception_when_trying_to_get_the_checksum_of_an_unexisting_file(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->willThrow($this->getBadResponseError(404));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringchecksum('test');
    }

    function it_throws_storage_failure_while_fetching_checksum(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringchecksum('test');
    }

    function it_fetches_metadata(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->getMetadata()->willReturn([
            'foo' => 'bar',
        ]);

        $this->getMetadata('test')->shouldReturn([
            'foo' => 'bar',
        ]);
    }

    function it_throws_file_not_found_exception_when_trying_to_get_the_metadata_of_an_unexisting_file(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->getMetadata()->willThrow($this->getBadResponseError(404));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringgetMetadata('test');
    }

    function it_throws_storage_failure_while_fetching_metadata(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->getMetadata()->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringgetMetadata('test');
    }

    function it_sets_metadata(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->resetMetadata([
            'foo' => 'bar',
        ])->shouldBeCalled();

        $this->setMetadata('test', ['foo' => 'bar'])->shouldNotThrow();
    }

    function it_throws_file_not_found_exception_when_trying_to_set_the_metadata_of_an_unexisting_file(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->resetMetadata(['foo' => 'bar'])->willThrow($this->getBadResponseError(404));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringsetMetadata('test', ['foo' => 'bar']);
    }

    function it_throws_storage_failure_while_setting_metadata(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->resetMetadata(['foo' => 'bar'])->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringsetMetadata('test', ['foo' => 'bar']);
    }

    function it_returns_mime_type(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->shouldBeCalled();
        $object->contentType = 'plain/text';

        $this->mimeType('test')->shouldReturn('plain/text');
    }

    function it_throws_file_not_found_exception_when_trying_to_get_the_mime_type_of_an_unexisting_file(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->willThrow($this->getBadResponseError(404));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringmimeType('test');
    }

    function it_throws_storage_failure_while_fetching_mime_type(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringmimeType('test');
    }

    function it_returns_size(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->shouldBeCalled();
        $object->contentLength = 42;

        $this->size('test')->shouldReturn(42);
    }

    function it_throws_file_not_found_exception_when_trying_to_get_the_size_of_an_unexisting_file(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->willThrow($this->getBadResponseError(404));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringsize('test');
    }

    function it_throws_storage_failure_while_fetching_size(Container $container, StorageObject $object)
    {
        $container->getObject('test')->willReturn($object);
        $object->retrieve()->willThrow($this->getBadResponseError(400));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringsize('test');
    }

    private function getReadableStream($content): StreamInterface
    {
        $stream = new BufferStream();
        $stream->write($content);

        return $stream;
    }

    private function getBadResponseError(int $statusCode): BadResponseError
    {
        $error = new BadResponseError();
        $error->setResponse(new Response($statusCode));

        return $error;
    }
}
