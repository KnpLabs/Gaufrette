<?php

namespace Gaufrette\Functional\Adapter;

use Aws\S3\S3Client;

trait AwsS3SetUpTearDownTrait
{
    /** @var S3Client */
    private $client;

    /** @var string */
    private $bucket;

    /** @var string */
    private $region;

    public function setUp()
    {
        $key    = getenv('AWS_KEY');
        $secret = getenv('AWS_SECRET');
        $region = getenv('AWS_REGION');

        if (empty($key) || empty($secret)) {
            $this->markTestSkipped('Missing AWS_KEY and/or AWS_SECRET env vars.');
        }

        $this->bucket = uniqid(getenv('AWS_BUCKET'));
        $this->region = $region ? $region : 'eu-west-1';

        if (self::$SDK_VERSION === 3) {
            // New way of instantiating S3Client for aws-sdk-php v3
            $this->client = new S3Client([
                'region' => $this->region,
                'version' => 'latest',
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);
        } else {
            $this->client = S3Client::factory([
                'region' => $this->region,
                'version' => '2006-03-01',
                'key' => $key,
                'secret' => $secret,
            ]);
        }
    }

    public function tearDown()
    {
        if ($this->client === null || !$this->client->doesBucketExist($this->bucket)) {
            return;
        }

        $result = $this->client->listObjects(['Bucket' => $this->bucket]);
        $staleObjects = $result->get('Contents');

        foreach ($staleObjects as $staleObject) {
            $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $staleObject['Key']]);
        }

        $this->client->deleteBucket(['Bucket' => $this->bucket]);
    }
}
