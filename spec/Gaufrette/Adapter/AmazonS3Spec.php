<?php

namespace spec\Gaufrette\Adapter;

use AmazonS3;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AmazonS3Spec extends ObjectBehavior
{
    /**
     * @param \AmazonS3 $service
     */
    function let(AmazonS3 $service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_supports_metadata()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_reads_file(AmazonS3 $service)
    {
        $options = [
            'range' => 12,
            'response' => [
                'content-language' => 'pl-pl',
            ],
        ];

        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->get_object(
                'bucketName',
                'filename',
                $options
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse('header', 'some content', 200))
        ;

        $this->setMetadata('filename', $options);
        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_returns_false_when_cannot_read(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->get_object(
                'bucketName',
                'filename',
                []
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse('header', 'some content', 500))
        ;

        $this->read('filename')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_is_verbose_and_throws_exceptions_when_read(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->get_object(
                'bucketName',
                'filename',
                []
            )
            ->willThrow(new \RuntimeException('read'))
        ;

        $this->shouldThrow(new \RuntimeException('read'))->duringRead('filename');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_rename_file(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->copy_object(
                [
                    'bucket' => 'bucketName',
                    'filename' => 'filename1',
                ],
                [
                    'bucket' => 'bucketName',
                    'filename' => 'filename2',
                ],
                ['acl' => \AmazonS3::ACL_OWNER_READ]
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse('header', 'some content', 200))
        ;
        $service
            ->delete_object(
                'bucketName',
                'filename1',
                Argument::any()
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse([], 'some', 200))
        ;

        $this->setMetadata('filename1', ['acl' => \AmazonS3::ACL_OWNER_READ]);
        $this->rename('filename1', 'filename2')->shouldReturn(true);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_is_verbose_and_throws_exceptions_when_rename(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->copy_object(Argument::cetera())
            ->willThrow(new \RuntimeException('rename'))
        ;

        $this->shouldThrow(new \RuntimeException('rename'))->duringRename('filename', 'filename1');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_returns_false_when_cannot_rename(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->copy_object(
                [
                    'bucket' => 'bucketName',
                    'filename' => 'filename1',
                ],
                [
                    'bucket' => 'bucketName',
                    'filename' => 'filename2',
                ],
                []
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse('header', 'some content', 500))
        ;

        $this->rename('filename1', 'filename2')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_write_file(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->create_object(
                'bucketName',
                'filename',
                [
                    'acl' => \AmazonS3::ACL_PRIVATE,
                    'body' => 'some content',
                ]
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(['x-aws-requestheaders' => ['Content-Length' => 12]], 'some content', 200))
        ;

        $this->setMetadata('filename', ['acl' => \AmazonS3::ACL_PRIVATE, 'body' => 'other content']);
        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_returns_false_when_cannot_write(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->create_object(
                'bucketName',
                'filename',
                [
                    'acl' => \AmazonS3::ACL_PUBLIC,
                    'body' => 'some content',
                ]
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(['x-aws-requestheaders' => ['Content-Length' => 12]], 'some content', 500))
        ;

        $this->write('filename', 'some content')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_is_verbose_and_throws_exceptions_when_write(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->create_object(Argument::cetera())
            ->willThrow(new \RuntimeException('write'))
        ;

        $this->shouldThrow(new \RuntimeException('write'))->duringWrite('filename', 'some content');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_check_if_file_exists(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;

        $service->if_object_exists('bucketName', 'filename')->willReturn(true);
        $this->exists('filename')->shouldReturn(true);

        $service->if_object_exists('bucketName', 'filename')->willReturn(false);
        $this->exists('filename')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_is_verbose_and_throws_exceptions_when_file_exists(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->if_object_exists('bucketName', 'filename')
            ->willThrow(new \RuntimeException('exists'))
        ;

        $this->shouldThrow(new \RuntimeException('exists'))->duringExists('filename');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_get_file_mtime(AmazonS3 $service)
    {
        $metadata = ['acl' => \AmazonS3::ACL_PUBLIC];
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;

        $service
            ->get_object_metadata(
                'bucketName',
                'filename',
                $metadata
            )
            ->shouldBeCalled()
            ->willReturn(['Headers' => ['last-modified' => '2012-01-01 23:10:10']])
        ;

        $this->setMetadata('filename', $metadata);
        $this->mtime('filename')->shouldReturn(strtotime('2012-01-01 23:10:10'));
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_returns_false_when_cannot_fetch_mtime(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;

        $service
            ->get_object_metadata(
                'bucketName',
                'filename',
                []
            )
            ->shouldBeCalled()
            ->willReturn(['Headers' => []])
        ;

        $this->mtime('filename')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_is_verbose_and_throws_exceptions_when_fetch_mtime(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->get_object_metadata('bucketName', 'filename', Argument::any())
            ->willThrow(new \RuntimeException('mtime'))
        ;

        $this->shouldThrow(new \RuntimeException('mtime'))->duringMtime('filename');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_delete_file(AmazonS3 $service)
    {
        $metadata = ['acl' => \AmazonS3::ACL_PRIVATE];

        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->delete_object(
                'bucketName',
                'filename',
                $metadata
            )
            ->willReturn(new \CFResponse([], 'some', 200))
        ;

        $this->setMetadata('filename', $metadata);
        $this->delete('filename')->shouldReturn(true);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_is_verbose_and_throws_exceptions_when_fetch_delete(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(true)
        ;
        $service
            ->delete_object(
                'bucketName',
                'filename',
                Argument::any()
            )
            ->willThrow(new \RuntimeException('delete'))
        ;

        $this->shouldThrow(new \RuntimeException('delete'))->duringDelete('filename');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_returns_false_when_cannot_delete(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->delete_object(
                'bucketName',
                'filename',
                []
            )
            ->willReturn(new \CFResponse([], 'some', 500))
        ;

        $this->delete('filename')->shouldReturn(false);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_get_keys(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->get_object_list('bucketName')
            ->shouldBeCalled()
            ->willReturn(['filename2', 'aaa/filename', 'filename1'])
        ;

        $this->keys()->shouldReturn(['aaa', 'aaa/filename', 'filename1', 'filename2']);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_is_verbose_and_throws_exceptions_when_fetch_keys(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(true)
        ;
        $service
            ->get_object_list('bucketName')
            ->willThrow(new \RuntimeException('keys'))
        ;

        $this->shouldThrow(new \RuntimeException('keys'))->duringKeys();
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_handle_dirs(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(true)
        ;
        $service
            ->if_object_exists('bucketName', 'filename')
            ->shouldNotBeCalled()
        ;
        $service
            ->if_object_exists('bucketName', 'filename/')
            ->shouldBeCalled()
            ->willReturn(false)
        ;
        $service
            ->if_object_exists('bucketName', 'dirname/')
            ->willReturn(true)
        ;

        $this->isDirectory('filename')->shouldReturn(false);
        $this->isDirectory('dirname')->shouldReturn(true);
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_should_fail_when_bucket_does_not_exist(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(false)
        ;
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringRead('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringWrite('filename', 'content')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringDelete('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringExists('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringMtime('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringRename('filename', 'filename2')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The configured bucket "bucketName" does not exist.'))
            ->duringKeys()
        ;
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_creates_bucket_if_create_mode_is_enabled(AmazonS3 $service)
    {
        $service->set_region(Argument::any())->shouldBeCalled();
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(false)
        ;
        $service->hostname = \AmazonS3::REGION_US_E1;
        $service
            ->create_bucket('bucketName', \AmazonS3::REGION_US_E1)
            ->shouldBeCalled()
            ->willReturn(new \CFResponse([], 'created', 201))
        ;
        $service
            ->if_object_exists('bucketName', 'filename')
            ->willReturn(false)
        ;

        $this->beConstructedWith($service, 'bucketName', ['create' => true]);
        $this->exists('filename');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_fails_when_cannot_create_bucket(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(false)
        ;
        $service
            ->create_bucket('bucketName', Argument::any())
            ->shouldBeCalled()
            ->willReturn(new \CFResponse([], 'created', 500))
        ;

        $this->beConstructedWith($service, 'bucketName', ['create' => true]);
        $this
            ->shouldThrow(new \RuntimeException('Failed to create the configured bucket "bucketName".'))
            ->duringExists('filename')
        ;
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_allows_to_configure_reqion(AmazonS3 $service)
    {
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(true)
        ;
        $service
            ->set_region(\AmazonS3::REGION_EU_W1)
            ->shouldBeCalled()
        ;
        $service
            ->if_object_exists('bucketName', 'filename')
            ->willReturn(true)
        ;

        $this->beConstructedWith($service, 'bucketName', ['region' => \AmazonS3::REGION_EU_W1]);
        $this->exists('filename');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_allows_to_configure_region_for_bucket(AmazonS3 $service)
    {
        $service->set_region(Argument::any())->shouldBeCalled();
        $service
            ->if_bucket_exists('bucketName')
            ->willReturn(false)
        ;
        $service
            ->create_bucket('bucketName', \AmazonS3::REGION_EU_W1)
            ->shouldBeCalled()
            ->willReturn(new \CFResponse([], 'created', 201))
        ;
        $service
            ->if_object_exists('bucketName', 'filename')
            ->willReturn(false)
        ;

        $this->beConstructedWith($service, 'bucketName', ['create' => true, 'region' => \AmazonS3::REGION_EU_W1]);
        $this->exists('filename');
    }

    /**
     * @param \AmazonS3 $service
     */
    function it_allows_to_configure_acl(AmazonS3 $service)
    {
        $this->setAcl('123abc');
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->create_object(
                'bucketName',
                'filename',
                [
                    'acl' => '123abc',
                    'body' => 'some content',
                ]
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(['x-aws-requestheaders' => ['Content-Length' => 12]], 'some content', 200))
        ;

        $this->write('filename', 'some content')->shouldReturn(12);
        $this->getAcl()->shouldBe('123abc');
    }

    /**
     * @param \AmazonS3 $service
     */
    function its_file_metadata_acl_are_more_important_than_global_acl_config(AmazonS3 $service)
    {
        $this->setAcl('123abc');
        $service
            ->if_bucket_exists('bucketName')
            ->shouldBeCalled()
            ->willReturn(true)
        ;
        $service
            ->create_object(
                'bucketName',
                'filename',
                [
                    'acl' => 'more important acl',
                    'body' => 'some content',
                ]
            )
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(['x-aws-requestheaders' => ['Content-Length' => 12]], 'some content', 200))
        ;

        $this->setMetadata('filename', ['acl' => 'more important acl']);
        $this->write('filename', 'some content')->shouldReturn(12);
    }
}
