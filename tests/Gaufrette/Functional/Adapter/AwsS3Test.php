<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\AmazonS3;
use Aws\S3\S3Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;

class AmazonS3Test extends \PHPUnit_Framework_TestCase
{
    protected function getClient()
    {
        return S3Client::factory(array(
            'key'    => 'foo',
            'secret' => 'bar'
        ));
    }

    public function testCreatesBucketIfMissing()
    {
        $mock = new MockPlugin(array(
            new Response(404),                // Head bucket response
            new Response(200),                // Create bucket response
            new Response(200, array(), 'foo') // Get object response
        ));
        $client = $this->getClient();
        $client->addSubscriber($mock);
        $adapter = new AmazonS3($client, 'bucket', array('create' => true));
        $this->assertEquals('foo', $adapter->read('foo'));

        $requests = $mock->getReceivedRequests();
        $this->assertEquals('HEAD', $requests[0]->getMethod());
        $this->assertEquals('PUT', $requests[1]->getMethod());
        $this->assertEquals('GET', $requests[2]->getMethod());
        $this->assertEquals('bucket.s3.amazonaws.com', $requests[0]->getHost());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowsExceptionIfBucketMissingAndNotCreating()
    {
        $mock = new MockPlugin(array(new Response(404)));
        $client = $this->getClient();
        $client->addSubscriber($mock);
        $adapter = new AmazonS3($client, 'bucket');
        $adapter->read('foo');
    }

    public function testDoesNotSupportDirectories()
    {
        $adapter = new AmazonS3($this->getClient(), 'bucket');
        $this->assertFalse($adapter->isDirectory('foo'));
    }

    public function testWritesObjects()
    {
        $mock = new MockPlugin(array(
            new Response(200), // HEAD bucket response
            new Response(201)  // PUT object response
        ));
        $client = $this->getClient();
        $client->addSubscriber($mock);
        $adapter = new AmazonS3($client, 'bucket');
        $this->assertEquals(7, $adapter->write('foo', 'testing'));
        $requests = $mock->getReceivedRequests();
        $this->assertEquals('bucket.s3.amazonaws.com', $requests[1]->getHost());
        $this->assertEquals('PUT', $requests[1]->getMethod());
    }

    public function testChecksForObjectExistence()
    {
        $mock = new MockPlugin(array(new Response(200)));
        $client = $this->getClient();
        $client->addSubscriber($mock);
        $adapter = new AmazonS3($client, 'bucket');
        $this->assertTrue($adapter->exists('foo'));
        $requests = $mock->getReceivedRequests();
        $this->assertEquals('bucket.s3.amazonaws.com', $requests[0]->getHost());
        $this->assertEquals('HEAD', $requests[0]->getMethod());
        $this->assertEquals('/foo', $requests[0]->getResource());
    }

    public function testGetsObjectUrls()
    {
        $client = $this->getClient();
        $adapter = new AmazonS3($client, 'bucket');
        $this->assertEquals('https://bucket.s3.amazonaws.com/foo', $adapter->getUrl('foo'));
    }
}
