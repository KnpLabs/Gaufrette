<?php

namespace spec\Gaufrette\Adapter;

use Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactoryInterface;
use PhpSpec\ObjectBehavior;
use WindowsAzure\Blob\Internal\IBlob;
use WindowsAzure\Blob\Models\Blob;
use WindowsAzure\Blob\Models\GetBlobResult;
use WindowsAzure\Blob\Models\ListBlobsResult;
use WindowsAzure\Common\ServiceException;

class AzureBlobStorageSpec extends ObjectBehavior
{
    public function let(BlobProxyFactoryInterface $blobProxyFactory): void
    {
        $this->beConstructedWith($blobProxyFactory, 'containerName');
    }

    public function it_should_be_initializable(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\AzureBlobStorage::class);
        $this->shouldHaveType(\Gaufrette\Adapter::class);
        $this->shouldHaveType(\Gaufrette\Adapter\MetadataSupporter::class);
    }

    public function it_should_read_file(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy, GetBlobResult $getBlobResult): void
    {
        $getBlobResult
            ->getContentStream()
            ->shouldBeCalled()
            //azure blob content is handled as stream so we need to fake it
            ->willReturn(fopen('data://text/plain,some content', 'r'));

        $blobProxy
            ->getBlob('containerName', 'filename')
            ->shouldBeCalled()
            ->willReturn($getBlobResult);

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->read('filename')->shouldReturn('some content');
    }

    public function it_should_return_false_when_cannot_read(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxy
            ->getBlob('containerName', 'filename')
            ->shouldBeCalled()
            ->willThrow(new ServiceException(500));

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->read('filename')->shouldReturn(false);
    }

    public function it_should_not_mask_exception_when_read(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxy
            ->getBlob('containerName', 'filename')
            ->shouldBeCalled()
            ->willThrow(new \RuntimeException('read'));

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->shouldThrow(new \RuntimeException('read'))->duringRead('filename');
    }

    public function it_should_rename_file(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxy
            ->copyBlob('containerName', 'filename2', 'containerName', 'filename1')
            ->shouldBeCalled();

        $blobProxy
            ->deleteBlob('containerName', 'filename1')
            ->shouldBeCalled();

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->rename('filename1', 'filename2')->shouldReturn(true);
    }

    public function it_should_return_false_when_cannot_rename(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxy
            ->copyBlob('containerName', 'filename2', 'containerName', 'filename1')
            ->shouldBeCalled()
            ->willThrow(new ServiceException(500));

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->rename('filename1', 'filename2')->shouldReturn(false);
    }

    public function it_should_not_mask_exception_when_rename(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxy
            ->copyBlob('containerName', 'filename2', 'containerName', 'filename1')
            ->shouldBeCalled()
            ->willThrow(new \RuntimeException('rename'));

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->shouldThrow(new \RuntimeException('rename'))->duringRename('filename1', 'filename2');
    }

    public function it_should_write_file(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxy
            ->createBlockBlob(
                'containerName',
                'filename',
                'some content',
                \Mockery::type('\WindowsAzure\Blob\Models\CreateBlobOptions')
            )
            ->shouldBeCalled();

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    public function it_should_return_false_when_cannot_write(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxy
            ->createBlockBlob(
                'containerName',
                'filename',
                'some content',
                \Mockery::type('\WindowsAzure\Blob\Models\CreateBlobOptions')
            )
            ->willThrow(new ServiceException(500));

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->write('filename', 'some content')->shouldReturn(false);
    }

    public function it_should_not_mask_exception_when_write(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxy
            ->createBlockBlob(
                'containerName',
                'filename',
                'some content',
                \Mockery::type('\WindowsAzure\Blob\Models\CreateBlobOptions')
            )
            ->willThrow(new \RuntimeException('write'));

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->shouldThrow(new \RuntimeException('write'))->duringWrite('filename', 'some content');
    }

    public function it_should_check_if_file_exists(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy, GetBlobResult $getBlobResult): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->getBlob('containerName', 'filename')
            ->shouldBeCalled()
            ->willThrow(new ServiceException(404));

        $this->exists('filename')->shouldReturn(false);

        $blobProxy
            ->getBlob('containerName', 'filename2')
            ->shouldBeCalled()
            ->willReturn($getBlobResult);

        $this->exists('filename2')->shouldReturn(true);
    }

    public function it_should_not_mask_exception_when_check_if_file_exists(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->getBlob('containerName', 'filename')
            ->shouldBeCalled()
            ->willThrow(new \RuntimeException('exists'));

        $this->shouldThrow(new \RuntimeException('exists'))->duringExists('filename');
    }

    public function it_should_get_file_mtime(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy, GetBlobPropertiesResult $getBlobPropertiesResult, BlobProperties $blobProperties): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->getBlobProperties('containerName', 'filename')
            ->shouldBeCalled()
            ->willReturn($getBlobPropertiesResult);

        $getBlobPropertiesResult
            ->getProperties()
            ->shouldBeCalled()
            ->willReturn($blobProperties);

        $blobProperties
            ->getLastModified()
            ->shouldBeCalled()
            ->willReturn(new \DateTime('1987-12-28 20:00:00'));

        $this->mtime('filename')->shouldReturn(strtotime('1987-12-28 20:00:00'));
    }

    public function it_should_return_false_when_cannot_mtime(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->getBlobProperties('containerName', 'filename')
            ->shouldBeCalled()
            ->willThrow(new ServiceException(500));

        $this->mtime('filename')->shouldReturn(false);
    }

    public function it_should_not_mask_exception_when_get_mtime(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->getBlobProperties('containerName', 'filename')
            ->shouldBeCalled()
            ->willThrow(new \RuntimeException('mtime'));

        $this->shouldThrow(new \RuntimeException('mtime'))->duringMtime('filename');
    }

    public function it_should_delete_file(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->deleteBlob('containerName', 'filename')
            ->shouldBeCalled();

        $this->delete('filename')->shouldReturn(true);
    }

    public function it_should_return_false_when_cannot_delete_file(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->deleteBlob('containerName', 'filename')
            ->shouldBeCalled()
            ->willThrow(new ServiceException(500));

        $this->delete('filename')->shouldReturn(false);
    }

    public function it_should_not_mask_exception_when_delete(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->deleteBlob('containerName', 'filename')
            ->shouldBeCalled()
            ->willThrow(new \RuntimeException('delete'));

        $this->shouldThrow(new \RuntimeException('delete'))->duringDelete('filename');
    }

    public function it_should_get_keys(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy, ListBlobsResult $listBlobResult): void
    {
        $fileNames = ['aaa', 'aaa/filename', 'filename1', 'filename2'];
        $blobs = [];
        foreach ($fileNames as $fileName) {
            $blob = new Blob();
            $blob->setName($fileName);
            $blobs[] = $blob;
        }

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->listBlobs('containerName')
            ->shouldBeCalled()
            ->willReturn($listBlobResult);

        $listBlobResult
            ->getBlobs()
            ->shouldBeCalled()
            ->willReturn($blobs);

        $this->keys()->shouldReturn(['aaa', 'aaa/filename', 'filename1', 'filename2']);
    }

    public function it_should_not_mask_exception_when_get_keys(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->listBlobs('containerName')
            ->shouldBeCalled()
            ->willThrow(new \RuntimeException('keys'));

        $this->shouldThrow(new \RuntimeException('keys'))->duringKeys();
    }

    public function it_should_handle_dirs(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->getBlob('containerName', 'filename')
            ->shouldNotBeCalled();
        $blobProxy
            ->getBlob('containerName', 'filename/')
            ->shouldBeCalled()
            ->willThrow(new ServiceException(404));
        $blobProxy
            ->getBlob('containerName', 'dirname/')
            ->shouldBeCalled();

        $this->isDirectory('filename')->shouldReturn(false);
        $this->isDirectory('dirname')->shouldReturn(true);
    }

    public function it_should_create_container(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->createContainer('containerName', null)
            ->shouldBeCalled();

        $this->createContainer('containerName');
    }

    public function it_should_fail_when_cannot_create_container(BlobProxyFactoryInterface $blobProxyFactory, IBlob $blobProxy): void
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->createContainer('containerName', null)
            ->shouldBeCalled()
            ->willThrow(new ServiceException(500));

        $this->shouldThrow(new \RuntimeException('Failed to create the configured container "containerName": 0 ().', null))->duringCreateContainer('containerName');
    }
}
