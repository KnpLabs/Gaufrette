<?php

namespace spec\Gaufrette\Adapter;

use Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactoryInterface;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\StorageFailure;
use MicrosoftAzure\Storage\Blob\Internal\IBlob;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\BlobProperties;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\GetBlobPropertiesResult;
use MicrosoftAzure\Storage\Blob\Models\GetBlobResult;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsResult;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;

class AzureBlobStorageSpec extends ObjectBehavior
{
    public function let(BlobProxyFactoryInterface $blobFactory)
    {
        $this->beConstructedWith($blobFactory, 'containerName');
    }

    public function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\AzureBlobStorage');
        $this->shouldHaveType('Gaufrette\Adapter');
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    public function it_reads_file(BlobProxyFactoryInterface $blobFactory, IBlob $blob, GetBlobResult $blobContent)
    {
        $blobFactory->create()->willReturn($blob);

        $blob->getBlob('containerName', 'filename')->willReturn($blobContent);
        $blobContent
            ->getContentStream()
            //azure blob content is handled as stream so we need to fake it
            ->willReturn(fopen('data://text/plain,some content','r'))
        ;

        $this->read('filename')->shouldReturn('some content');
    }

    public function it_throws_storage_failure_if_it_fails_to_read_file(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->getBlob('containerName', 'filename')->willThrow($azureException->getWrappedObject());
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(500);

        $this->shouldThrow(StorageFailure::class)->duringRead('filename');
    }

    public function it_throws_file_not_found_if_read_file_does_not_exist(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->getBlob('containerName', 'filename')->willThrow($azureException->getWrappedObject());
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(404);

        $this->shouldThrow(FileNotFound::class)->duringRead('filename');
    }

    public function it_renames_file(BlobProxyFactoryInterface $blobFactory, IBlob $blob)
    {
        $blobFactory->create()->willReturn($blob);

        $blob->copyBlob('containerName', 'filename2', 'containerName', 'filename1')->shouldBeCalled();
        $blob->deleteBlob('containerName', 'filename1')->shouldBeCalled();

        $this->shouldNotThrow(\Exception::class)->duringRename('filename1', 'filename2');
    }

    public function it_throws_storage_failure_when_rename_fail(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob
            ->copyBlob('containerName', 'filename2', 'containerName', 'filename1')
            ->willThrow($azureException->getWrappedObject())
        ;
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(500);

        $this->shouldThrow(StorageFailure::class)->duringRename('filename1', 'filename2');
    }

    public function it_throws_file_not_found_when_renamed_file_does_not_exist(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob
            ->copyBlob('containerName', 'filename2', 'containerName', 'filename1')
            ->willThrow($azureException->getWrappedObject())
        ;
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(404);

        $this->shouldThrow(FileNotFound::class)->duringRename('filename1', 'filename2');
    }

    public function it_writes_file(BlobProxyFactoryInterface $blobFactory, IBlob $blob)
    {
        $blobFactory->create()->willReturn($blob);

        $blob
            ->createBlockBlob('containerName', 'filename', 'some content', Argument::type(CreateBlobOptions::class))
            ->shouldBeCalled()
        ;

        $this->shouldNotThrow(StorageFailure::class)->duringWrite('filename', 'some content');
    }

    public function it_throws_storage_failure_when_write_fail(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob
            ->createBlockBlob('containerName', 'filename', 'some content', Argument::type(CreateBlobOptions::class))
            ->willThrow($azureException->getWrappedObject())
        ;
        $response->getStatusCode()->willReturn(500);

        $this->shouldThrow(StorageFailure::class)->duringWrite('filename', 'some content');
    }

    public function it_checks_if_file_exists(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        GetBlobResult $blobContent,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->getBlob('containerName', 'filename')->willThrow($azureException->getWrappedObject());
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(404);
        $this->exists('filename')->shouldReturn(false);

        $blob->getBlob('containerName', 'filename2')->willReturn($blobContent);
        $this->exists('filename2')->shouldReturn(true);
    }

    public function it_throws_storage_failure_when_it_fails_to_assert_if_a_file_exists(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob
            ->getBlob('containerName', 'filename')
            ->willThrow($azureException->getWrappedObject())
        ;
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(500);

        $this->shouldThrow(StorageFailure::class)->duringExists('filename');
    }

    public function it_gets_file_mtime(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        GetBlobPropertiesResult $blobPropertiesResult,
        BlobProperties $blobProperties
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->getBlobProperties('containerName', 'filename')->willReturn($blobPropertiesResult);
        $blobPropertiesResult->getProperties()->willReturn($blobProperties);
        $blobProperties->getLastModified()->willReturn(new \DateTime('1987-12-28 20:00:00'));

        $this->mtime('filename')->shouldReturn(strtotime('1987-12-28 20:00:00'));
    }

    public function it_throws_storage_failure_when_it_fails_to_get_file_mtime(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->getBlobProperties('containerName', 'filename')->willThrow($azureException->getWrappedObject());
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(500);

        $this->shouldThrow(StorageFailure::class)->duringMtime('filename');
    }

    public function it_throws_file_not_found_when_it_fails_to_get_file_mtime(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->getBlobProperties('containerName', 'filename')->willThrow($azureException->getWrappedObject());
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(404);

        $this->shouldThrow(FileNotFound::class)->duringMtime('filename');
    }

    public function it_deletes_file(BlobProxyFactoryInterface $blobFactory, IBlob $blob)
    {
        $blobFactory->create()->willReturn($blob);

        $blob->deleteBlob('containerName', 'filename')->shouldBeCalled();

        $this->shouldNotThrow(StorageFailure::class)->duringDelete('filename');
    }

    public function it_throws_storage_failure_when_it_fails_to_delete_file(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->deleteBlob('containerName', 'filename')->willThrow($azureException->getWrappedObject());
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(500);

        $this->shouldThrow(StorageFailure::class)->duringDelete('filename');
    }

    public function it_throws_file_not_found_when_it_fails_to_delete_file(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->deleteBlob('containerName', 'filename')->willThrow($azureException->getWrappedObject());
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(404);

        $this->shouldThrow(FileNotFound::class)->duringDelete('filename');
    }

    public function it_should_get_keys(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        Blob $blobFooBar,
        Blob $blobBaz,
        ListBlobsResult $listBlobResult
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->listBlobs('containerName')->willReturn($listBlobResult);
        $listBlobResult->getBlobs()->willReturn([$blobFooBar, $blobBaz]);

        $blobFooBar->getName()->willReturn('foo/bar');
        $blobBaz->getName()->willReturn('baz');

        $this->keys()->shouldReturn(['foo/bar', 'baz']);
    }

    public function it_throws_storage_failure_when_it_fails_to_get_keys(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->listBlobs('containerName')->willThrow($azureException->getWrappedObject());
        $azureException->getResponse()->willReturn($response);
        $response->getStatusCode()->willReturn(500);

        $this->shouldThrow(StorageFailure::class)->duringKeys();
    }

    public function it_creates_container(BlobProxyFactoryInterface $blobFactory, IBlob $blob)
    {
        $blobFactory->create()->willReturn($blob);

        $blob->createContainer('containerName', null)->shouldBeCalled();

        $this->createContainer('containerName');
    }

    public function it_throws_storage_failure_when_it_fails_to_create_container(
        BlobProxyFactoryInterface $blobFactory,
        IBlob $blob,
        ServiceException $azureException,
        ResponseInterface $response
    ) {
        $blobFactory->create()->willReturn($blob);

        $blob->createContainer('containerName', null)->willThrow($azureException->getWrappedObject());
        $azureException->getResponse()->willReturn($response);
        $response->getBody()->willReturn('<Code>SomeErrorCode</Code>');
        $azureException->getErrorText()->willReturn(Argument::type('string'));

        $this->shouldThrow(StorageFailure::class)->duringCreateContainer('containerName');
    }
}
