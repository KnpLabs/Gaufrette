<?php

namespace Gaufrette\Functional\Adapter;

/**
 * Functional tests for the BackblazeB2Storage adapter.
 *
 * Copy the ../adapters/BackblazeB2Storage.php.dist to BackblazeB2Storage.php and
 * adapt to your needs.
 *
 * @author  Kamil Zabdyr <kamilzabdyr@gmail.com>
 */
class BackblazeB2StorageTest extends FunctionalTestCase
{
    /**
     * @test
     * @group functional
     *
     * @expectedException \RuntimeException
     */
    public function shouldThrowExceptionIfBucketMissing()
    {
        /** @var \Gaufrette\Adapter\BackblazeB2Storage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $oldBucket = $adapter->getOptions();
        $adapter->setBucket('Gaufrette-' . mt_rand());

        $adapter->read('foo');
        $adapter->setBucket($oldBucket);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWriteAndReadWithDirectory()
    {
        /** @var \Gaufrette\Adapter\BackblazeB2Storage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $oldOptions = $adapter->getOptions();
        $adapter->setOptions(array('directory' => 'Gaufrette'));

        $this->assertEquals(12, $this->filesystem->write('foo', 'Some content'));
        $this->assertEquals(13, $this->filesystem->write('test/subdir/foo', 'Some content1', true));

        $this->assertEquals('Some content', $this->filesystem->read('foo'));
        $this->assertEquals('Some content1', $this->filesystem->read('test/subdir/foo'));

        $this->filesystem->delete('foo');
        $this->filesystem->delete('test/subdir/foo');
        $adapter->setOptions($oldOptions);
    }
}
