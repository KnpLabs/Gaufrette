<?php

namespace Gaufrette\Functional\Adapter;

use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\MockHandler;
use Gaufrette\Adapter\AwsS3;

/**
 * @todo move to phpspec
 */
class AwsS3Test extends \PHPUnit_Framework_TestCase
{
    protected function getClient(MockHandler $mock = null)
    {
        return new S3Client([
            'handler'     => $mock,
            'region'      => 'eu-west-1',
            'version'     => 'latest',
            'credentials' => [
                'key'    => 'foo',
                'secret' => 'bar',
            ],
        ]);
    }

    private function mockException(array $context = [])
    {
        return function (CommandInterface $command) use ($context) {
            return new S3Exception('Mock exception', $command, $context);
        };
    }

    public function testCreatesBucketIfMissing()
    {
        $mock = new MockHandler([
            $this->mockException(['code' => 'NoSuchBucket']),
            new Result(),
            new Result(['Body' => 'foo']),
        ]);
        $client = $this->getClient($mock);
        $adapter = new AwsS3($client, 'bucket', array('create' => true));

        $this->assertEquals('foo', $adapter->read('foo'));
        $this->assertEquals(0, $mock->count());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowsExceptionIfBucketMissingAndNotCreating()
    {
        $mock = new MockHandler([
            $this->mockException(['code' => 'NoSuchBucket'])
        ]);
        $client = $this->getClient($mock);
        $adapter = new AwsS3($client, 'bucket');

        $adapter->read('foo');

    }

    public function testWritesObjects()
    {
        $mock = new MockHandler([
            new Result(),
            new Result(),
        ]);
        $client = $this->getClient($mock);
        $adapter = new AwsS3($client, 'bucket');

        $this->assertEquals(7, $adapter->write('foo', 'testing'));
        $this->assertEquals(0, $mock->count());
    }

    public function testChecksForObjectExistence()
    {
        $mock = new MockHandler([new Result()]);
        $client = $this->getClient($mock);
        $adapter = new AwsS3($client, 'bucket');

        $this->assertTrue($adapter->exists('foo'));
        $this->assertEquals(0, $mock->count());
    }

    public function testGetsObjectUrls()
    {
        $client = $this->getClient();
        $adapter = new AwsS3($client, 'bucket');
        $this->assertEquals('https://s3-eu-west-1.amazonaws.com/bucket/foo', $adapter->getUrl('foo'));
    }

    public function testChecksForObjectExistenceWithDirectory()
    {
        $mock = new MockHandler([new Result()]);
        $client = $this->getClient($mock);
        $adapter = new AwsS3($client, 'bucket', array('directory' => 'bar'));

        $this->assertTrue($adapter->exists('foo'));
        $this->assertEquals(0, $mock->count());
    }

    public function testGetsObjectUrlsWithDirectory()
    {
        $client = $this->getClient();
        $adapter = new AwsS3($client, 'bucket', array('directory' => 'bar'));
        $this->assertEquals('https://s3-eu-west-1.amazonaws.com/bucket/bar/foo', $adapter->getUrl('foo'));
    }

    public function shouldListKeysWithoutDirectory()
    {
        $client = $this->getClient();
        $adapter = new AwsS3($client, 'bucket', array('directory' => 'bar'));
        $adapter->write('test.txt', 'some content');
        $keys = $adapter->listKeys();
        $this->assertEquals('test.txt', $keys['key']);
    }
}
