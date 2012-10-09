<?php

namespace spec\Gaufrette\Adapter;

use PHPSpec2\ObjectBehavior;

class AmazonS3 extends ObjectBehavior
{
    /**
     * @param \AmazonS3 $service
     */
    function let($service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\AmazonS3');
        $this->shouldHaveType('Gaufrette\Adapter');
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_read_file($service)
    {
        $options = array(
            'range' => 12,
            'response' => array(
                'content-language' => 'pl-pl'
            )
        );

        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);
        $service
            ->get_object(
                'bucketName',
                'filename',
                $options
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse('header', 'some content', 200));

        $this->setMetadata('filename', $options);
        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_return_false_when_cannot_read($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);
        $service
            ->get_object(
                'bucketName',
                'filename',
                array()
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse('header', 'some content', 500));

        $this->read('filename')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_rename_file($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);
        $service
            ->copy_object(
                array(
                    'bucket'   => 'bucketName',
                    'filename' => 'filename1',
                ),
                array(
                    'bucket'   => 'bucketName',
                    'filename' => 'filename2'
                ),
                array('acl' => \AmazonS3::ACL_OWNER_READ)
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse('header', 'some content', 200));

       $this->setMetadata('filename1', array('acl' => \AmazonS3::ACL_OWNER_READ));
       $this->rename('filename1', 'filename2')->shouldReturn(true);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_return_false_when_cannot_rename($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);
        $service
            ->copy_object(
                array(
                    'bucket'   => 'bucketName',
                    'filename' => 'filename1',
                ),
                array(
                    'bucket'   => 'bucketName',
                    'filename' => 'filename2'
                ),
                array()
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse('header', 'some content', 500));

       $this->rename('filename1', 'filename2')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_write_file($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);
        $service
            ->create_object(
                'bucketName',
                'filename',
                array(
                    'acl' => \AmazonS3::ACL_PRIVATE,
                    'content' => 'some content'
                )
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array('x-aws-requestheaders' => array('Content-Length' => 12)), 'some content', 200));

        $this->setMetadata('filename', array('acl' => \AmazonS3::ACL_PRIVATE, 'content' => 'other content'));
        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_return_false_when_cannot_write($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);
        $service
            ->create_object(
                'bucketName',
                'filename',
                array(
                    'acl' => \AmazonS3::ACL_PUBLIC,
                    'content' => 'some content'
                )
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array('x-aws-requestheaders' => array('Content-Length' => 12)), 'some content', 500));

        $this->write('filename', 'some content')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_check_if_file_exists($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);

        $service->if_object_exists('bucketName', 'filename')->willReturn(true);
        $this->exists('filename')->shouldReturn(true);

        $service->if_object_exists('bucketName', 'filename')->willReturn(false);
        $this->exists('filename')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_get_file_mtime($service)
    {
        $metadata = array('acl' => \AmazonS3::ACL_PUBLIC);
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);

        $service
            ->get_object_metadata(
                'bucketName',
                'filename',
                $metadata
            )
            ->shouldBeCalled()
            ->willReturn(array('Headers' => array('last-modified' => '2012-01-01 23:10:10')));

       $this->setMetadata('filename', $metadata);
       $this->mtime('filename')->shouldReturn(strtotime('2012-01-01 23:10:10'));
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_return_false_when_cannot_mtime($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);

        $service
            ->get_object_metadata(
                'bucketName',
                'filename',
                array()
            )
            ->shouldBeCalled()
            ->willReturn(array('Headers' => array()));

       $this->mtime('filename')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_delete_file($service)
    {
        $metadata = array('acl' => \AmazonS3::ACL_PRIVATE);

        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);
        $service
            ->delete_object(
                'bucketName',
                'filename',
                $metadata
            )
            ->willReturn(new \CFResponse(array(), 'some', 200));

        $this->setMetadata('filename', $metadata);
        $this->delete('filename')->shouldReturn(true);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_return_false_when_cannot_delete_file($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);
        $service
            ->delete_object(
                'bucketName',
                'filename',
                array()
            )
            ->willReturn(new \CFResponse(array(), 'some', 500));

        $this->delete('filename')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_get_keys($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true);
        $service
            ->get_object_list('bucketName')
            ->shouldBeCalled()
            ->willReturn(array('filename2', 'filename', 'filename1'));

        $this->keys()->shouldReturn(array('filename', 'filename1', 'filename2'));
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_not_handle_dirs($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(true);
        $service
            ->if_object_exists('bucketName', 'filename')
            ->willReturn(true);

        $this->isDirectory('filename')->shouldReturn(false);
        $this->isDirectory('other')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_fail_when_bucket_does_not_exist($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(false);

        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringRead('filename');
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringWrite('filename', 'content');
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringDelete('filename');
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringExists('filename');
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringMtime('filename');
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringRename('filename', 'filename2');
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringKeys();
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_create_bucket_if_create_mode_is_enabled($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(false);
        $service
            ->create_bucket('bucketName', \AmazonS3::REGION_US_E1)
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array(), 'created', 201));
        $service
            ->if_object_exists('bucketName', 'filename')
            ->willReturn(false);

        $this->beConstructedWith($service, 'bucketName', array('create' => true));
        $this->exists('filename');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_allows_to_configure_region_when_creating_bucket($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(false);
        $service
            ->create_bucket('bucketName', \AmazonS3::REGION_EU_W1)
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array(), 'created', 201));
        $service
            ->if_object_exists('bucketName', 'filename')
            ->willReturn(false);

        $this->beConstructedWith($service, 'bucketName', array('create' => true, 'region' => \AmazonS3::REGION_EU_W1));
        $this->exists('filename');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_fail_when_cannot_create_bucket($service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(false);
        $service
            ->create_bucket('bucketName', \AmazonS3::REGION_US_E1)
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array(), 'created', 500));

        $this->beConstructedWith($service, 'bucketName', array('create' => true));
        $this
            ->shouldThrow(new \RuntimeException('Failed to create the configured bucket "bucketName".'))
            ->duringExists('filename');
    }
}
