<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Aws\S3\S3Client;
use Gaufrette\Util;

/**
 * Amazon S3 adapter using the AWS SDK for PHP v2.x.
 *
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class AwsS3 implements Adapter, MetadataSupporter, ListKeysAware, SizeCalculator, MimeTypeProvider
{
    protected S3Client $service;
    protected string $bucket;
    protected array $options;
    protected bool $bucketExists;
    protected array $metadata = [];
    protected bool $detectContentType;

    public function __construct(
        S3Client $service,
        string $bucket,
        array $options = [],
        bool $detectContentType = false
    ) {
        if (!class_exists(S3Client::class)) {
            throw new \LogicException('You need to install package "aws/aws-sdk-php" to use this adapter');
        }
        $this->service = $service;
        $this->bucket = $bucket;
        $this->options = array_replace(
            [
                'create' => false,
                'directory' => '',
                'acl' => 'private',
            ],
            $options
        );
        $this->detectContentType = $detectContentType;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(string $key, array $metadata): void
    {
        // BC with AmazonS3 adapter
        if (isset($metadata['contentType'])) {
            $metadata['ContentType'] = $metadata['contentType'];
            unset($metadata['contentType']);
        }

        $this->metadata[$key] = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(string $key): array
    {
        return $this->metadata[$key] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        $this->ensureBucketExists();
        $options = $this->getOptions($key);

        try {
            // Get remote object
            $object = $this->service->getObject($options);
            // If there's no metadata array set up for this object, set it up
            if (!array_key_exists($key, $this->metadata) || !is_array($this->metadata[$key])) {
                $this->metadata[$key] = [];
            }
            // Make remote ContentType metadata available locally
            $this->metadata[$key]['ContentType'] = $object->get('ContentType');

            return (string) $object->get('Body');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        $this->ensureBucketExists();
        $options = $this->getOptions(
            $targetKey,
            ['CopySource' => $this->bucket . '/' . $this->computePath($sourceKey)]
        );

        try {
            $this->service->copyObject(array_merge($options, $this->getMetadata($targetKey)));

            return $this->delete($sourceKey);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, mixed $content): int|bool
    {
        $this->ensureBucketExists();
        $options = $this->getOptions($key, ['Body' => $content]);

        /*
         * If the ContentType was not already set in the metadata, then we autodetect
         * it to prevent everything being served up as binary/octet-stream.
         */
        if (!isset($options['ContentType']) && $this->detectContentType) {
            $options['ContentType'] = $this->guessContentType($content);
        }

        try {
            $this->service->putObject($options);

            if (is_resource($content)) {
                return Util\Size::fromResource($content);
            }

            return Util\Size::fromContent($content);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return $this->service->doesObjectExist($this->bucket, $this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        try {
            $result = $this->service->headObject($this->getOptions($key));

            return strtotime($result['LastModified']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $key): int
    {
        try {
            $result = $this->service->headObject($this->getOptions($key));

            return $result['ContentLength'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function mimeType(string $key): bool|string
    {
        try {
            $result = $this->service->headObject($this->getOptions($key));

            return ($result['ContentType']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return $this->listKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
    {
        $this->ensureBucketExists();

        $options = ['Bucket' => $this->bucket];
        if ((string) $prefix != '') {
            $options['Prefix'] = $this->computePath($prefix);
        } elseif (!empty($this->options['directory'])) {
            $options['Prefix'] = $this->options['directory'];
        }

        $keys = [];
        $iter = $this->service->getIterator('ListObjects', $options);
        foreach ($iter as $file) {
            $keys[] = $this->computeKey($file['Key']);
        }

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        try {
            $this->service->deleteObject($this->getOptions($key));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        $result = $this->service->listObjects([
            'Bucket' => $this->bucket,
            'Prefix' => rtrim($this->computePath($key), '/') . '/',
            'MaxKeys' => 1,
        ]);
        if (isset($result['Contents'])) {
            if (is_array($result['Contents']) || $result['Contents'] instanceof \Countable) {
                return count($result['Contents']) > 0;
            }
        }

        return false;
    }

    /**
     * Ensures the specified bucket exists. If the bucket does not exists
     * and the create option is set to true, it will try to create the
     * bucket. The bucket is created using the same region as the supplied
     * client object.
     *
     * @throws \RuntimeException if the bucket does not exists or could not be
     *                           created
     */
    protected function ensureBucketExists(): bool
    {
        if ($this->bucketExists) {
            return true;
        }

        if ($this->bucketExists = $this->service->doesBucketExist($this->bucket)) {
            return true;
        }

        if (!$this->options['create']) {
            throw new \RuntimeException(sprintf(
                'The configured bucket "%s" does not exist.',
                $this->bucket
            ));
        }

        $this->service->createBucket([
            'Bucket' => $this->bucket,
            'LocationConstraint' => $this->service->getRegion(),
        ]);
        $this->bucketExists = true;

        return true;
    }

    protected function getOptions(string $key, array $options = []): array
    {
        $options['ACL'] = $this->options['acl'];
        $options['Bucket'] = $this->bucket;
        $options['Key'] = $this->computePath($key);

        /*
         * Merge global options for adapter, which are set in the constructor, with metadata.
         * Metadata will override global options.
         */
        $options = array_merge($this->options, $options, $this->getMetadata($key));

        return $options;
    }

    protected function computePath(string $key): string
    {
        if (empty($this->options['directory'])) {
            return $key;
        }

        return sprintf('%s/%s', $this->options['directory'], $key);
    }

    /**
     * Computes the key from the specified path.
     */
    protected function computeKey(string $path): string
    {
        return ltrim(substr($path, strlen($this->options['directory'])), '/');
    }

    private function guessContentType(mixed $content): bool|string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        if (is_resource($content)) {
            return $fileInfo->file(stream_get_meta_data($content)['uri']);
        }

        return $fileInfo->buffer($content);
    }
}
