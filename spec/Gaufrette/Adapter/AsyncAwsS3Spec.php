<?php

namespace spec\Gaufrette\Adapter;

use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\Core\Waiter;
use AsyncAws\S3\Result\BucketExistsWaiter;
use AsyncAws\SimpleS3\SimpleS3Client;
use Gaufrette\Adapter\MimeTypeProvider;
use PhpSpec\ObjectBehavior;

class AsyncAwsS3Spec extends ObjectBehavior
{
    public function let(SimpleS3Client $service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\AsyncAwsS3');
    }

    public function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    public function it_supports_metadata()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    public function it_supports_sizecalculator()
    {
        $this->shouldHaveType('Gaufrette\Adapter\SizeCalculator');
    }

    public function it_provides_mime_type()
    {
        $this->shouldHaveType(MimeTypeProvider::class);
    }

    public function it_checks_the_bucket_before_first_use(SimpleS3Client $service)
    {
        $waiter = ResultMockFactory::waiter(BucketExistsWaiter::class, Waiter::STATE_SUCCESS);
        $service->bucketExists(['Bucket' => 'bucketName'])->willReturn($waiter)->shouldBeCalledOnce();
        $service->getObject(\Prophecy\Argument::any())->willThrow(new \RuntimeException('not found'));

        $this->read('key')->shouldReturn(false);
    }
}
