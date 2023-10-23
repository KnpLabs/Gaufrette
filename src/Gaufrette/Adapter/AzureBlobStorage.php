<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Util;
use Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactoryInterface;
use MicrosoftAzure\Storage\Blob\Internal\IBlob;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\BlobServiceOptions;
use MicrosoftAzure\Storage\Blob\Models\Container;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

/**
 * Microsoft Azure Blob Storage adapter.
 *
 * @author Luciano Mammino <lmammino@oryzone.com>
 * @author Paweł Czyżewski <pawel.czyzewski@enginewerk.com>
 */
class AzureBlobStorage implements Adapter, MetadataSupporter, SizeCalculator, ChecksumCalculator, MimeTypeProvider
{
    /**
     * Error constants.
     */
    const ERROR_CONTAINER_ALREADY_EXISTS = 'ContainerAlreadyExists';
    const ERROR_CONTAINER_NOT_FOUND = 'ContainerNotFound';

    protected ?IBlob $blobProxy = null;

    protected bool $multiContainerMode = false;

    protected CreateContainerOptions $createContainerOptions;

    public function __construct(
        private readonly BlobProxyFactoryInterface $blobProxyFactory,
        private readonly ?string $containerName = null,
        bool $create = false,
        private readonly bool $detectContentType = true
    ) {
        if (null === $containerName) {
            $this->multiContainerMode = true;
        } elseif ($create) {
            $this->createContainer($containerName);
        }
    }

    public function getCreateContainerOptions(): CreateContainerOptions
    {
        return $this->createContainerOptions;
    }

    public function setCreateContainerOptions(CreateContainerOptions $options)
    {
        $this->createContainerOptions = $options;
    }

    /**
     * Creates a new container.
     *
     * @throws \RuntimeException if cannot create the container
     */
    public function createContainer(string $containerName, CreateContainerOptions $options = null)
    {
        $this->init();

        if (null === $options) {
            $options = $this->getCreateContainerOptions();
        }

        try {
            $this->blobProxy->createContainer($containerName, $options);
        } catch (ServiceException $e) {
            $errorCode = $this->getErrorCodeFromServiceException($e);

            if ($errorCode !== self::ERROR_CONTAINER_ALREADY_EXISTS) {
                throw new \RuntimeException(sprintf(
                    'Failed to create the configured container "%s": %s (%s).',
                    $containerName,
                    $e->getErrorText(),
                    $errorCode
                ));
            }
        }
    }

    /**
     * Deletes a container.
     *
     * @throws \RuntimeException if cannot delete the container
     */
    public function deleteContainer(string $containerName, BlobServiceOptions $options = null)
    {
        $this->init();

        try {
            $this->blobProxy->deleteContainer($containerName, $options);
        } catch (ServiceException $e) {
            $errorCode = $this->getErrorCodeFromServiceException($e);

            if ($errorCode !== self::ERROR_CONTAINER_NOT_FOUND) {
                throw new \RuntimeException(sprintf(
                    'Failed to delete the configured container "%s": %s (%s).',
                    $containerName,
                    $e->getErrorText(),
                    $errorCode
                ), $e->getCode());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $blob = $this->blobProxy->getBlob($containerName, $key);

            return stream_get_contents($blob->getContentStream());
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, sprintf('read key "%s"', $key), $containerName);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, mixed $content): int|bool
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        if (class_exists(CreateBlockBlobOptions::class)) {
            $options = new CreateBlockBlobOptions();
        } else {
            // for microsoft/azure-storage < 1.0
            $options = new CreateBlobOptions();
        }

        if ($this->detectContentType) {
            $contentType = $this->guessContentType($content);

            $options->setContentType($contentType);
        }

        $size = is_resource($content)
            ? Util\Size::fromResource($content)
            : Util\Size::fromContent($content)
        ;

        try {
            if ($this->multiContainerMode) {
                $this->createContainer($containerName);
            }

            $this->blobProxy->createBlockBlob($containerName, $key, $content, $options);
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, sprintf('write content for key "%s"', $key), $containerName);

            return false;
        }

        return $size;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        $listBlobsOptions = new ListBlobsOptions();
        $listBlobsOptions->setPrefix($key);

        try {
            $blobsList = $this->blobProxy->listBlobs($containerName, $listBlobsOptions);

            foreach ($blobsList->getBlobs() as $blob) {
                if ($key === $blob->getName()) {
                    return true;
                }
            }
        } catch (ServiceException $e) {
            $errorCode = $this->getErrorCodeFromServiceException($e);
            if ($this->multiContainerMode && self::ERROR_CONTAINER_NOT_FOUND === $errorCode) {
                return false;
            }
            $this->failIfContainerNotFound($e, 'check if key exists', $containerName);

            throw new \RuntimeException(sprintf(
                'Failed to check if key "%s" exists in container "%s": %s (%s).',
                $key,
                $containerName,
                $e->getErrorText(),
                $errorCode
            ), $e->getCode());
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        $this->init();

        try {
            if ($this->multiContainerMode) {
                $containersList = $this->blobProxy->listContainers();

                return call_user_func_array('array_merge', array_map(
                    function (Container $container) {
                        $containerName = $container->getName();

                        return $this->fetchBlobs($containerName, $containerName);
                    },
                    $containersList->getContainers()
                ));
            }

            return $this->fetchBlobs($this->containerName);
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, 'retrieve keys', $this->containerName);
            $errorCode = $this->getErrorCodeFromServiceException($e);

            throw new \RuntimeException(sprintf(
                'Failed to list keys for the container "%s": %s (%s).',
                $this->containerName,
                $e->getErrorText(),
                $errorCode
            ), $e->getCode());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $properties = $this->blobProxy->getBlobProperties($containerName, $key);

            return $properties->getProperties()->getLastModified()->getTimestamp();
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, sprintf('read mtime for key "%s"', $key), $containerName);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $key): int
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $properties = $this->blobProxy->getBlobProperties($containerName, $key);

            return $properties->getProperties()->getContentLength();
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, sprintf('read content length for key "%s"', $key), $containerName);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(string $key): string
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $properties = $this->blobProxy->getBlobProperties($containerName, $key);

            return $properties->getProperties()->getContentType();
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, sprintf('read content mime type for key "%s"', $key), $containerName);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checksum(string $key): string|bool
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $properties = $this->blobProxy->getBlobProperties($containerName, $key);
            $checksumBase64 = $properties->getProperties()->getContentMD5();

            return \bin2hex(\base64_decode($checksumBase64, true));
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, sprintf('read content MD5 for key "%s"', $key), $containerName);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $this->blobProxy->deleteBlob($containerName, $key);

            return true;
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, sprintf('delete key "%s"', $key), $containerName);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        $this->init();

        list($sourceContainerName, $sourceKey) = $this->tokenizeKey($sourceKey);
        list($targetContainerName, $targetKey) = $this->tokenizeKey($targetKey);

        try {
            if ($this->multiContainerMode) {
                $this->createContainer($targetContainerName);
            }
            $this->blobProxy->copyBlob($targetContainerName, $targetKey, $sourceContainerName, $sourceKey);
            $this->blobProxy->deleteBlob($sourceContainerName, $sourceKey);

            return true;
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, sprintf('rename key "%s"', $sourceKey), $sourceContainerName);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        // Windows Azure Blob Storage does not support directories
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(string $key, array $content): void
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $this->blobProxy->setBlobMetadata($containerName, $key, $content);
        } catch (ServiceException $e) {
            $errorCode = $this->getErrorCodeFromServiceException($e);

            throw new \RuntimeException(sprintf(
                'Failed to set metadata for blob "%s" in container "%s": %s (%s).',
                $key,
                $containerName,
                $e->getErrorText(),
                $errorCode
            ), $e->getCode());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(string $key): array
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $properties = $this->blobProxy->getBlobProperties($containerName, $key);

            return $properties->getMetadata();
        } catch (ServiceException $e) {
            $errorCode = $this->getErrorCodeFromServiceException($e);

            throw new \RuntimeException(sprintf(
                'Failed to get metadata for blob "%s" in container "%s": %s (%s).',
                $key,
                $containerName,
                $e->getErrorText(),
                $errorCode
            ), $e->getCode());
        }
    }

    /**
     * Lazy initialization, automatically called when some method is called after construction.
     */
    protected function init(): void
    {
        if ($this->blobProxy === null) {
            $this->blobProxy = $this->blobProxyFactory->create();
        }
    }

    /**
     * Throws a runtime exception if a give ServiceException derived from a "container not found" error.
     *
     * @throws \RuntimeException
     */
    protected function failIfContainerNotFound(ServiceException $exception, string $action, string $containerName): void
    {
        $errorCode = $this->getErrorCodeFromServiceException($exception);

        if ($errorCode === self::ERROR_CONTAINER_NOT_FOUND) {
            throw new \RuntimeException(sprintf(
                'Failed to %s: container "%s" not found.',
                $action,
                $containerName
            ), $exception->getCode());
        }
    }

    /**
     * Extracts the error code from a service exception.
     */
    protected function getErrorCodeFromServiceException(ServiceException $exception): string
    {
        $xml = @simplexml_load_string($exception->getResponse()->getBody());

        if ($xml && isset($xml->Code)) {
            return (string) $xml->Code;
        }

        return $exception->getErrorText();
    }

    /**
     * @param string|resource $content
     */
    private function guessContentType($content): string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        if (is_resource($content)) {
            return $fileInfo->file(stream_get_meta_data($content)['uri']);
        }

        return $fileInfo->buffer($content);
    }

    /**
     * @return array{string, string}
     */
    private function tokenizeKey(string $key): array
    {
        $containerName = $this->containerName;
        if (false === $this->multiContainerMode) {
            return [$containerName, $key];
        }

        if (false === ($index = strpos($key, '/'))) {
            throw new \InvalidArgumentException(sprintf(
                'Failed to establish container name from key "%s", container name is required in multi-container mode',
                $key
            ));
        }
        $containerName = substr($key, 0, $index);
        $key = substr($key, $index + 1);

        return [$containerName, $key];
    }

    /**
     * @return array<string>
     */
    private function fetchBlobs(string $containerName, ?string $prefix = null): array
    {
        $blobList = $this->blobProxy->listBlobs($containerName);

        return array_map(
            function (Blob $blob) use ($prefix) {
                $name = $blob->getName();
                if (null !== $prefix) {
                    $name = $prefix . '/' . $name;
                }

                return $name;
            },
            $blobList->getBlobs()
        );
    }
}
