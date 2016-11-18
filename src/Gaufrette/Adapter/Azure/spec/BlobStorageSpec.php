<?php

namespace spec\Gaufrette\Adapter\Azure;

use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use WindowsAzure\Common\ServiceException;

class BlobStorageSpec extends ObjectBehavior
{
    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     */
    public function let($blobProxyFactory)
    {
        $this->beConstructedWith($blobProxyFactory, 'containerName');
    }

    public function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\Azure\BlobStorage');
        $this->shouldHaveType('Gaufrette\Adapter');
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     * @param \MicrosoftAzure\Storage\Blob\Models\GetBlobResult  $getBlobResult
     */
    public function it_should_read_file($blobProxyFactory, $blobProxy, $getBlobResult)
    {
        $getBlobResult
            ->getContentStream()
            ->shouldBeCalled()
            //azure blob content is handled as stream so we need to fake it
            ->willReturn(fopen('data://text/plain,some content','r'));

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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_return_false_when_cannot_read($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_not_mask_exception_when_read($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_rename_file($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_return_false_when_cannot_rename($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_not_mask_exception_when_rename($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_write_file($blobProxyFactory, $blobProxy)
    {
        $blobProxy
            ->createBlockBlob('containerName', 'filename', 'some content', Argument::type(CreateBlobOptions::class))
            ->shouldBeCalled();

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_return_false_when_cannot_write($blobProxyFactory, $blobProxy)
    {
        $blobProxy
            ->createBlockBlob('containerName', 'filename', 'some content', Argument::type(CreateBlobOptions::class))
            ->willThrow(new ServiceException(500));

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->write('filename', 'some content')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_not_mask_exception_when_write($blobProxyFactory, $blobProxy)
    {
        $blobProxy
            ->createBlockBlob('containerName', 'filename', 'some content', Argument::type(CreateBlobOptions::class))
            ->willThrow(new \RuntimeException('write'));

        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $this->shouldThrow(new \RuntimeException('write'))->duringWrite('filename', 'some content');
    }

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface           $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob                  $blobProxy
     * @param \MicrosoftAzure\Storage\Blob\Models\GetBlobPropertiesResult  $getBlobPropertiesResult
     * @param \MicrosoftAzure\Storage\Blob\Models\BlobProperties           $blobProperties
     */
    public function it_should_get_file_mtime($blobProxyFactory, $blobProxy, $getBlobPropertiesResult, $blobProperties)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_return_false_when_cannot_mtime($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_not_mask_exception_when_get_mtime($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_delete_file($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_return_false_when_cannot_delete_file($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_not_mask_exception_when_delete($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface   $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob          $blobProxy
     * @param \MicrosoftAzure\Storage\Blob\Models\ListBlobsResult  $listBlobResult
     */
    public function it_should_get_keys($blobProxyFactory, $blobProxy, $listBlobResult)
    {
        $fileNames = array('aaa', 'aaa/filename', 'filename1', 'filename2');
        $blobs = array();
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

        $this->keys()->shouldReturn(array('aaa', 'aaa/filename', 'filename1', 'filename2'));
    }

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_not_mask_exception_when_get_keys($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_create_container($blobProxyFactory, $blobProxy)
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

    /**
     * @param \Gaufrette\Adapter\Azure\BlobProxyFactoryInterface $blobProxyFactory
     * @param \MicrosoftAzure\Storage\Blob\Internal\IBlob        $blobProxy
     */
    public function it_should_fail_when_cannot_create_container($blobProxyFactory, $blobProxy)
    {
        $blobProxyFactory
            ->create()
            ->shouldBeCalled()
            ->willReturn($blobProxy);

        $blobProxy
            ->createContainer('containerName', null)
            ->shouldBeCalled()
            ->willThrow(new ServiceException(500));

        $this->shouldThrow(new \RuntimeException('Failed to create the configured container "containerName":  ().', null))->duringCreateContainer('containerName');
    }

}
