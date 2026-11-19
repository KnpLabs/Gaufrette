<?php

namespace spec\Gaufrette\Adapter;

use Google\Service\Storage\Objects as StorageObjects;
use Google\Service\Storage\Resource\Buckets;
use Google\Service\Storage\Resource\Objects;
use Google\Service\Storage\StorageObject;
use PhpSpec\ObjectBehavior;

class GoogleCloudStorageSpec extends ObjectBehavior
{
    public function let(\Google_Service_Storage $service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    public function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    public function it_supports_metadata()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    public function it_is_list_keys_aware()
    {
        $this->shouldHaveType('Gaufrette\Adapter\ListKeysAware');
    }

    public function it_lists_keys_without_deprecations(\Google_Service_Storage $service, Buckets $buckets, Objects $objects)
    {
        $list = new StorageObjects();
        $list->setItems([new StorageObject(['name' => 'b']), new StorageObject(['name' => 'a'])]);
        $objects->listObjects('bucketName', [])->willReturn($list);
        $service->getWrappedObject()->buckets = $buckets->getWrappedObject();
        $service->getWrappedObject()->objects = $objects->getWrappedObject();

        // setAccessible() is deprecated since PHP 8.5 (#731)
        set_error_handler(function (int $errno, string $errstr, string $errfile) {
            if (!str_ends_with($errfile, 'GoogleCloudStorage.php')) {
                return false;
            }

            throw new \ErrorException($errstr, 0, $errno, $errfile);
        }, E_DEPRECATED);

        try {
            $this->listKeys()->shouldReturn(['a', 'b']);
        } finally {
            restore_error_handler();
        }
    }
}
