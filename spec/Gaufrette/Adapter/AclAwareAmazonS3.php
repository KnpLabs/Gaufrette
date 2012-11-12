<?php

namespace spec\Gaufrette\Adapter;

use PHPSpec2\ObjectBehavior;

class AclAwareAmazonS3 extends ObjectBehavior
{
    /**
     * @param \Gaufrette\Adapter $adapter
     * @param \AmazonS3 $service
     */
    function let($adapter, $service)
    {
        $this->beConstructedWith($adapter, $service, 'bucketName');
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\AclAwareAmazonS3');
        $this->shouldHaveType('Gaufrette\Adapter');
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     */
    function it_should_delegate_read($adapter)
    {
        $adapter->read('filename')->willReturn('some content');
        $adapter->read('filename2')->willReturn('other content');

        $this->read('filename')->shouldReturn('some content');
        $this->read('filename2')->shouldReturn('other content');
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     * @param \AmazonS3 $service
     */
    function it_should_delegate_rename_and_update_acl($adapter, $service)
    {
        $service
            ->set_object_acl('bucketName', 'filename2', \AmazonS3::ACL_PRIVATE)
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array(), '', 200));
        $adapter
            ->rename('filename', 'filename2')
            ->shouldBeCalled()
            ->willReturn(true);
        $adapter
            ->delete('filename')
            ->shouldNotBeCalled();

        $this->rename('filename', 'filename2')->shouldReturn(true);
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     * @param \AmazonS3 $service
     */
    function it_should_not_rename_when_cannot_update_acl($adapter, $service)
    {
        $service
            ->set_object_acl('bucketName', 'filename2', \AmazonS3::ACL_PRIVATE)
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array(), '', 500));
        $adapter
            ->rename('filename', 'filename2')
            ->shouldBeCalled()
            ->willReturn(true);
        $adapter
            ->delete('filename2')
            ->shouldBeCalled();

        $this->rename('filename', 'filename2')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     * @param \AmazonS3 $service
     */
    function it_should_update_acl_with_users_array_when_rename($adapter, $service)
    {
        $service
            ->set_object_acl('bucketName', 'filename2', array(array('id' => 'someId', 'permission' => \AmazonS3::GRANT_READ)))
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array(), '', 200));
        $adapter
            ->rename('filename', 'filename2')
            ->willReturn(true);

        $this->setUsers(array(array('id' => 'someId', 'permission' => 'read')));
        $this->rename('filename', 'filename2')->shouldReturn(true);
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     * @param \AmazonS3 $service
     */
    function it_should_delegate_write_and_update_acl($adapter, $service)
    {
        $service
            ->set_object_acl('bucketName', 'filename', \AmazonS3::ACL_PRIVATE)
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array(), '', 200));
        $adapter
            ->write('filename', 'some content')
            ->shouldBeCalled()
            ->willReturn(12);
        $adapter
            ->delete('filename')
            ->shouldNotBeCalled();

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     * @param \AmazonS3 $service
     */
    function it_should_not_write_when_cannot_update_acl($adapter, $service)
    {
        $service
            ->set_object_acl('bucketName', 'filename', \AmazonS3::ACL_PRIVATE)
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array(), '', 500));
        $adapter
            ->write('filename', 'some content')
            ->shouldBeCalled()
            ->willReturn(12);
        $adapter
            ->delete('filename')
            ->shouldBeCalled();

        $this->write('filename', 'some content')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     * @param \AmazonS3 $service
     */
    function it_should_update_acl_with_users_array_when_write($adapter, $service)
    {
        $service
            ->set_object_acl('bucketName', 'filename', array(array('id' => 'someId', 'permission' => \AmazonS3::GRANT_READ)))
            ->shouldBeCalled()
            ->willReturn(new \CFResponse(array(), '', 200));
        $adapter
            ->write('filename', 'some content')
            ->willReturn(12);

        $this->setUsers(array(array('id' => 'someId', 'permission' => 'read')));
        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     */
    function it_should_delegate_exists($adapter)
    {
        $adapter->exists('filename')->willReturn(true);
        $adapter->exists('filename2')->willReturn(false);

        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename2')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     */
    function it_should_delegate_mtime($adapter)
    {
        $adapter->mtime('filename')->willReturn(1234);
        $adapter->mtime('filename2')->willReturn(2345);

        $this->mtime('filename')->shouldReturn(1234);
        $this->mtime('filename2')->shouldReturn(2345);
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     */
    function it_should_delegate_is_directory_check($adapter)
    {
        $adapter->isDirectory('filename')->willReturn(true);
        $adapter->isDirectory('filename2')->willReturn(false);

        $this->isDirectory('filename')->shouldReturn(true);
        $this->isDirectory('filename2')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     */
    function it_should_delegate_keys($adapter)
    {
        $adapter->keys->willReturn(array('filename', 'filename2'));

        $this->keys()->shouldReturn(array('filename', 'filename2'));
    }

    /**
     * @param \spec\Gaufrette\Adapter\TestDelegateAdapter $extendedAdapter
     * @param \AmazonS3 $service
     */
    function it_should_delegate_metadata($extendedAdapter, $service)
    {
        $this->beConstructedWith($extendedAdapter, $service, 'bucketName');

        $extendedAdapter->setMetadata('filename', array('some'))->shouldBeCalled();
        $extendedAdapter->getMetadata('filename')->shouldBeCalled()->willReturn(array('some2'));

        $this->setMetadata('filename', array('some'));
        $this->getMetadata('filename')->shouldReturn(array('some2'));
    }

    /**
     * @param \Gaufrette\Adapter $adapter
     */
    function it_should_not_delegate_metadata_when_delegate_object_does_not_support_it($adapter)
    {
        $adapter->setMetadata('filename', array('some'))->shouldNotBeCalled();
        $adapter->getMetadata('filename')->shouldNotBeCalled();

        $this->setMetadata('filename', array('some'));
        $this->getMetadata('filename')->shouldReturn(array());
    }
}

interface TestDelegateAdapter extends \Gaufrette\Adapter,
                                      \Gaufrette\Adapter\MetadataSupporter
{
}
