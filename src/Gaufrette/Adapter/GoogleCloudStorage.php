<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Google\Service\Storage;
use Google\Service\Storage\Bucket;
use Google\Service\Storage\StorageObject;
use Google\Service\Exception as ServiceException;
use Google\Service\Storage\BucketIamConfiguration;
use Google\Service\Storage\BucketIamConfigurationUniformBucketLevelAccess;
use GuzzleHttp;
use RuntimeException;
use ReflectionException;

/**
 * Google Cloud Storage adapter using the Google APIs Client Library for PHP.
 *
 * @author  Patrik Karisch <patrik@karisch.guru>
 */
class GoogleCloudStorage implements Adapter, MetadataSupporter, ListKeysAware
{
    public const OPTION_CREATE_BUCKET_IF_NOT_EXISTS = 'create';
    public const OPTION_PROJECT_ID = 'project_id';
    public const OPTION_LOCATION = 'bucket_location';
    public const OPTION_STORAGE_CLASS = 'storage_class';

    protected $service;
    protected $bucket;
    protected $options = [
        self::OPTION_CREATE_BUCKET_IF_NOT_EXISTS => false,
        self::OPTION_STORAGE_CLASS => 'STANDARD',
        'directory' => '',
        'acl' => 'private',
    ];
    protected $bucketExists;
    protected $metadata = [];
    protected $detectContentType;

    /**
     * @param Storage $service           The storage service class with authenticated
     *                                                   client and full access scope
     * @param string                  $bucket            The bucket name
     * @param array                   $options           Options can be directory and acl
     * @param bool                    $detectContentType Whether to detect the content type or not
     */
    public function __construct(
        Storage $service,
        string $bucket,
        array $options = [],
        bool $detectContentType = false
    ) {
        if (!class_exists(Storage::class)) {
            throw new \LogicException('You need to install package "google/apiclient" to use this adapter');
        }

        $this->service = $service;
        $this->bucket = $bucket;
        $this->options = array_replace(
            $this->options,
            $options
        );

        $this->detectContentType = $detectContentType;
    }

    /**
     * @return array<string, mixed> The actual options
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, mixed> $options The new options
     */
    public function setOptions(array $options): void
    {
        $this->options = array_replace($this->options, $options);
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

    /**
     * Sets a new bucket name.
     */
    public function setBucket(string $bucket): void
    {
        $this->bucketExists = null;
        $this->bucket = $bucket;
    }

    public function read(string $key): string|bool
    {
        $this->ensureBucketExists();
        $path = $this->computePath($key);

        $object = $this->getObjectData($path);
        if ($object === false) {
            return false;
        }

        if (class_exists('Google_Http_Request')) {
            $request = new \Google_Http_Request($object->getMediaLink());
            $this->service->getClient()->getAuth()->sign($request);
            $response = $this->service->getClient()->getIo()->executeRequest($request);
            if ($response[2] == 200) {
                $this->setMetadata($key, $object->getMetadata());

                return $response[0];
            }
        } else {
            $httpClient = new GuzzleHttp\Client();
            $httpClient = $this->service->getClient()->authorize($httpClient);
            $response = $httpClient->request('GET', $object->getMediaLink());
            if ($response->getStatusCode() == 200) {
                $this->setMetadata($key, $object->getMetadata());

                return $response->getBody();
            }
        }

        return false;
    }

    public function write(string $key, mixed $content): int|bool
    {
        $this->ensureBucketExists();
        $path = $this->computePath($key);

        $metadata = $this->getMetadata($key);
        $options = [
            'uploadType' => 'multipart',
            'data' => $content,
        ];

        /*
         * If the ContentType was not already set in the metadata, then we autodetect
         * it to prevent everything being served up as application/octet-stream.
         */
        if (!isset($metadata['ContentType']) && $this->detectContentType) {
            $options['mimeType'] = $this->guessContentType($content);
            unset($metadata['ContentType']);
        } elseif (isset($metadata['ContentType'])) {
            $options['mimeType'] = $metadata['ContentType'];
            unset($metadata['ContentType']);
        }

        $object = new StorageObject();
        $object->name = $path;

        if (isset($metadata['ContentDisposition'])) {
            $object->setContentDisposition($metadata['ContentDisposition']);
            unset($metadata['ContentDisposition']);
        }

        if (isset($metadata['CacheControl'])) {
            $object->setCacheControl($metadata['CacheControl']);
            unset($metadata['CacheControl']);
        }

        if (isset($metadata['ContentLanguage'])) {
            $object->setContentLanguage($metadata['ContentLanguage']);
            unset($metadata['ContentLanguage']);
        }

        if (isset($metadata['ContentEncoding'])) {
            $object->setContentEncoding($metadata['ContentEncoding']);
            unset($metadata['ContentEncoding']);
        }

        $object->setMetadata($metadata);

        try {
            $object = $this->service->objects->insert($this->bucket, $object, $options);

            if ($this->options['acl'] == 'public') {
                $acl = new \Google_Service_Storage_ObjectAccessControl();
                $acl->setEntity('allUsers');
                $acl->setRole('READER');

                $this->service->objectAccessControls->insert($this->bucket, $path, $acl);
            }

            return $object->getSize();
        } catch (ServiceException $e) {
            return false;
        }
    }

    public function exists(string $key): bool
    {
        $this->ensureBucketExists();
        $path = $this->computePath($key);

        try {
            $this->service->objects->get($this->bucket, $path);
        } catch (ServiceException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return $this->listKeys();
    }

    public function mtime(string $key): int|bool
    {
        $this->ensureBucketExists();
        $path = $this->computePath($key);

        $object = $this->getObjectData($path);

        return $object ? strtotime($object->getUpdated()) : false;
    }

    public function delete(string $key): bool
    {
        $this->ensureBucketExists();
        $path = $this->computePath($key);

        try {
            $this->service->objects->delete($this->bucket, $path);
        } catch (ServiceException $e) {
            return false;
        }

        return true;
    }

    public function rename(string $sourceKey, string $targetKey): bool
    {
        $this->ensureBucketExists();
        $sourcePath = $this->computePath($sourceKey);
        $targetPath = $this->computePath($targetKey);

        $object = $this->getObjectData($sourcePath);
        if ($object === false) {
            return false;
        }

        try {
            $this->service->objects->copy($this->bucket, $sourcePath, $this->bucket, $targetPath, $object);
            $this->service->objects->delete($this->bucket, $sourcePath);
        } catch (ServiceException $e) {
            return false;
        }

        return true;
    }

    public function isDirectory(string $key): bool
    {
        if ($this->exists($key . '/')) {
            return true;
        }

        return false;
    }

    /**
     * @return array<int, string>
     * @throws RuntimeException
     * @throws ReflectionException
     */
    public function listKeys(string $prefix = ''): array
    {
        $this->ensureBucketExists();

        $options = [];
        if ((string) $prefix != '') {
            $options['prefix'] = $this->computePath($prefix);
        } elseif (!empty($this->options['directory'])) {
            $options['prefix'] = $this->options['directory'];
        }

        $list = $this->service->objects->listObjects($this->bucket, $options);
        $keys = [];

        // FIXME: Temporary workaround for google/google-api-php-client#375
        $reflectionClass = new \ReflectionClass('Google_Service_Storage_Objects');
        $reflectionProperty = $reflectionClass->getProperty('collection_key');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($list, 'items');

        /** @var StorageObject $object */
        foreach ($list as $object) {
            $keys[] = $object->name;
        }

        sort($keys);

        return $keys;
    }

    /**
     * @param array<string, mixed> $content
     */
    public function setMetadata(string $key, array $content): void
    {
        $path = $this->computePath($key);

        $this->metadata[$path] = $content;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(string $key): array
    {
        $path = $this->computePath($key);

        return $this->metadata[$path] ?? [];
    }

    /**
     * Ensures the specified bucket exists.
     *
     * @throws \RuntimeException if the bucket does not exists
     */
    protected function ensureBucketExists(): void
    {
        if ($this->bucketExists) {
            return;
        }

        try {
            $this->service->buckets->get($this->bucket);
            $this->bucketExists = true;

            return;
        } catch (ServiceException $e) {
            if ($this->options[self::OPTION_CREATE_BUCKET_IF_NOT_EXISTS]) {
                if (!isset($this->options[self::OPTION_PROJECT_ID])) {
                    throw new \RuntimeException(
                        sprintf('Option "%s" missing, cannot create bucket', self::OPTION_PROJECT_ID)
                    );
                }
                if (!isset($this->options[self::OPTION_LOCATION])) {
                    throw new \RuntimeException(
                        sprintf('Option "%s" missing, cannot create bucket', self::OPTION_LOCATION)
                    );
                }

                $bucketIamConfigDetail = new BucketIamConfigurationUniformBucketLevelAccess();
                $bucketIamConfigDetail->setEnabled(true);
                $bucketIam = new BucketIamConfiguration();
                $bucketIam->setUniformBucketLevelAccess($bucketIamConfigDetail);
                $bucket = new Bucket();
                $bucket->setName($this->bucket);
                $bucket->setLocation($this->options[self::OPTION_LOCATION]);
                $bucket->setStorageClass($this->options[self::OPTION_STORAGE_CLASS]);
                $bucket->setIamConfiguration($bucketIam);

                $this->service->buckets->insert(
                    $this->options[self::OPTION_PROJECT_ID],
                    $bucket
                );

                $this->bucketExists = true;

                return;
            }

            $this->bucketExists = false;

            throw new \RuntimeException(
                sprintf(
                    'The configured bucket "%s" does not exist.',
                    $this->bucket
                )
            );
        }
    }

    protected function computePath(string $key): string
    {
        if (empty($this->options['directory'])) {
            return $key;
        }

        return sprintf('%s/%s', $this->options['directory'], $key);
    }

    /**
     * @param string $path
     * @param array<string, mixed>  $options
     */
    private function getObjectData(string $path, array $options = []): bool|StorageObject
    {
        try {
            return $this->service->objects->get($this->bucket, $path, $options);
        } catch (ServiceException $e) {
            return false;
        }
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function guessContentType(string $content): string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        if (is_resource($content)) {
            return $fileInfo->file(stream_get_meta_data($content)['uri']);
        }

        return $fileInfo->buffer($content);
    }
}
