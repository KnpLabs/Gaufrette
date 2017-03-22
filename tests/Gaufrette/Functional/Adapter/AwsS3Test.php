<?php

namespace Gaufrette\Functional\Adapter;

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;

/**
 * @todo move to phpspec
 */
class AwsS3Test extends \PHPUnit_Framework_TestCase
{
    static private $SDK_VERSION = 3;

    /** @var string */
    private $bucket;

    /** @var S3Client */
    private $client;

    public static function setUpBeforeClass()
    {
        $installed = json_decode(file_get_contents(__DIR__.'/../../../../vendor/composer/installed.json'), true);
        $sdk = current(array_filter($installed, function ($dependency) {
            return $dependency['name'] === 'aws/aws-sdk-php';
        }));

        if (version_compare($sdk['version'], '3.0.0') === -1) {
            self::$SDK_VERSION = 2;
        }
    }

    public function setUp()
    {
        $key = getenv('AWS_KEY');
        $secret = getenv('AWS_SECRET');

        if (empty($key) || empty($secret)) {
            $this->markTestSkipped();
        }

        $this->bucket = uniqid(getenv('AWS_BUCKET'));

        if (self::$SDK_VERSION === 3) {
            $this->client = new S3Client([
                'region' => 'eu-west-1',
                'version' => 'latest',
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);
        } else {
            $this->client = S3Client::factory([
                'region' => 'eu-west-1',
                'version' => '2006-03-01',
                'key' => $key,
                'secret' => $secret,
            ]);
        }
    }

    public function tearDown()
    {
        if ($this->bucket === null) {
            return;
        }

        $result = $this->client->listObjects(['Bucket' => $this->bucket]);
        $staleObjects = $result->get('Contents');

        foreach ($staleObjects as $staleObject) {
            $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $staleObject['Key']]);
        }

        $this->client->deleteBucket(['Bucket' => $this->bucket]);
    }

    private function getFilesystem(array $adapterOptions = [])
    {
        return new Filesystem(new AwsS3($this->client, $this->bucket, $adapterOptions));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowsExceptionIfBucketMissingAndNotCreating()
    {
        $this->bucket = null;
        $filesystem = $this->getFilesystem();
        $filesystem->read('foo');
    }

    public function testWritesObjects()
    {
        $filesystem = $this->getFilesystem(['create' => true]);
        $this->assertEquals(7, $filesystem->write('foo', 'testing'));
    }

    public function testChecksForObjectExistence()
    {
        $filesystem = $this->getFilesystem(['create' => true]);
        $filesystem->write('foo', '');
        $this->assertTrue($filesystem->has('foo'));
    }

    public function testGetsObjectUrls()
    {
        $filesystem = $this->getFilesystem(['create' => true]);

        $this->assertEquals(
            sprintf('https://%s.s3-eu-west-1.amazonaws.com/foo', $this->bucket),
            $filesystem->getAdapter()->getUrl('foo')
        );
        $this->bucket = null;
    }

    public function testChecksForObjectExistenceWithDirectory()
    {
        $filesystem = $this->getFilesystem(['directory' => 'bar', 'create' => true]);
        $filesystem->write('foo', '');

        $this->assertTrue($filesystem->has('foo'));
    }

    public function testGetsObjectUrlsWithDirectory()
    {
        $filesystem = $this->getFilesystem(['directory' => 'bar']);

        $this->assertEquals(
            sprintf('https://%s.s3-eu-west-1.amazonaws.com/bar/foo', $this->bucket),
            $filesystem->getAdapter()->getUrl('foo')
        );
        $this->bucket = null;
    }

    public function shouldListKeysWithoutDirectory()
    {
        $filesystem = $this->getFilesystem();
        $filesystem->write('test.txt', 'some content');
        $keys = $filesystem->listKeys();
        $this->assertEquals('test.txt', $keys['key']);
    }
}
