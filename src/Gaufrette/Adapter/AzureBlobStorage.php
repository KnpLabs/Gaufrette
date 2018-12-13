<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\InvalidKey;
use Gaufrette\Exception\StorageFailure;
use Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactoryInterface;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

/**
 * Microsoft Azure Blob Storage adapter.
 *
 * @author Luciano Mammino <lmammino@oryzone.com>
 * @author Paweł Czyżewski <pawel.czyzewski@enginewerk.com>
 */
class AzureBlobStorage implements Adapter,
                                  MetadataSupporter,
                                  SizeCalculator,
                                  ChecksumCalculator

{
    /**
     * Error constants.
     */
    const ERROR_CONTAINER_ALREADY_EXISTS = 'ContainerAlreadyExists';
    const ERROR_CONTAINER_NOT_FOUND = 'ContainerNotFound';

    /**
     * @var AzureBlobStorage\BlobProxyFactoryInterface
     */
    protected $blobProxyFactory;

    /**
     * @var string
     */
    protected $containerName;

    /**
     * @var bool
     */
    protected $detectContentType;

    /**
     * @var \MicrosoftAzure\Storage\Blob\Internal\IBlob
     */
    protected $blobProxy;

    /**
     * @var bool
     */
    protected $multiContainerMode = false;

    /**
     * @var CreateContainerOptions
     */
    protected $createContainerOptions;

    /**
     * @param AzureBlobStorage\BlobProxyFactoryInterface $blobProxyFactory
     * @param string|null                                $containerName
     * @param bool                                       $create
     * @param bool                                       $detectContentType
     *
     * @throws \RuntimeException
     */
    public function __construct(BlobProxyFactoryInterface $blobProxyFactory, $containerName = null, $create = false, $detectContentType = true)
    {
        $this->blobProxyFactory = $blobProxyFactory;
        $this->containerName = $containerName;
        $this->detectContentType = $detectContentType;

        if (null === $containerName) {
            $this->multiContainerMode = true;
        } elseif ($create) {
            $this->createContainer($containerName);
        }
    }

    /**
     * @return CreateContainerOptions
     */
    public function getCreateContainerOptions()
    {
        return $this->createContainerOptions;
    }

    /**
     * @param CreateContainerOptions $options
     */
    public function setCreateContainerOptions(CreateContainerOptions $options)
    {
        $this->createContainerOptions = $options;
    }

    /**
     * Creates a new container.
     *
     * @param string                                                     $containerName
     * @param \MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions $options
     *
     * @throws StorageFailure if cannot create the container
     */
    public function createContainer($containerName, CreateContainerOptions $options = null)
    {
        $this->init();

        if (null === $options) {
            $options = $this->getCreateContainerOptions();
        }

        try {
            $this->blobProxy->createContainer($containerName, $options);
        } catch (ServiceException $e) {
            $errorCode = $this->getErrorCodeFromServiceException($e);

            // We don't care if the container was created between check and creation attempt
            // it might be due to a parallel execution creating it.
            if ($errorCode !== self::ERROR_CONTAINER_ALREADY_EXISTS) {
                throw StorageFailure::unexpectedFailure('createContainer', [
                    'containerName' => $containerName,
                    'options' => $options,
                ], $e);
            }
        }
    }

    /**
     * Deletes a container.
     *
     * @param string                 $containerName
     * @param DeleteContainerOptions $options
     *
     * @throws StorageFailure if cannot delete the container
     */
    public function deleteContainer($containerName, DeleteContainerOptions $options = null)
    {
        $this->init();

        try {
            $this->blobProxy->deleteContainer($containerName, $options);
        } catch (ServiceException $e) {
            throw StorageFailure::unexpectedFailure('deleteContainer', [
                'containerName' => $containerName,
            ], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $blob = $this->blobProxy->getBlob($containerName, $key);

            return stream_get_contents($blob->getContentStream());
        } catch (ServiceException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new FileNotFound($key);
            }

            throw StorageFailure::unexpectedFailure('read', [
                'containerName' => $containerName,
                'key' => $key,
            ], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        $options = new CreateBlockBlobOptions();

        if ($this->detectContentType) {
            $contentType = $this->guessContentType($content);

            $options->setContentType($contentType);
        }

        try {
            if ($this->multiContainerMode) {
                $this->createContainer($containerName);
            }

            $this->blobProxy->createBlockBlob($containerName, $key, $content, $options);
        } catch (ServiceException $e) {
            throw StorageFailure::unexpectedFailure('write', [
                'containerName' => $containerName,
                'key' => $key,
            ], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $this->blobProxy->getBlob($containerName, $key);

            return true;
        } catch (ServiceException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return false;
            }

            throw StorageFailure::unexpectedFailure('exists', [
                'containerName' => $containerName,
                'key' => $key,
            ], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        $this->init();

        if ($this->multiContainerMode) {
            return $this->keysForMultiContainerMode();
        }

        return $this->keysForSingleContainerMode();
    }

    /**
     * List objects stored when the adapter is used in multi container mode.
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    private function keysForMultiContainerMode()
    {
        try {
            $containersList = $this->blobProxy->listContainers()->getContainers();
            $lists = [];

            foreach ($containersList as $container) {
                $lists[] = $this->fetchContainerKeysAndIgnore404($container->getName());
            }

            return !empty($lists) ? array_merge(...$lists) : [];
        } catch (ServiceException $e) {
            throw StorageFailure::unexpectedFailure('keys', [
                'multiContainerMode' => $this->multiContainerMode,
                'containerName' => $this->containerName,
            ], $e);
        }
    }

    /**
     * List the keys in a container and ignore any 404 error.
     *
     * This prevent race conditions happening when a container is deleted after calling listContainers() and
     * before the container keys are fetched.
     *
     * @param string $containerName
     *
     * @return array
     */
    private function fetchContainerKeysAndIgnore404($containerName)
    {
        try {
            return $this->fetchBlobs($containerName, $containerName);
        } catch (ServiceException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return [];
            }

            throw $e;
        }
    }

    /**
     * List objects stored when the adapter is not used in multi container mode.
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    private function keysForSingleContainerMode()
    {
        try {
            return $this->fetchBlobs($this->containerName);
        } catch (ServiceException $e) {
            throw StorageFailure::unexpectedFailure('keys', [
                'multiContainerMode' => $this->multiContainerMode,
                'containerName' => $this->containerName,
            ], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $properties = $this->blobProxy->getBlobProperties($containerName, $key);

            return $properties->getProperties()->getLastModified()->getTimestamp();
        } catch (ServiceException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new FileNotFound($key);
            }

            throw StorageFailure::unexpectedFailure('mtime', [
                'containerName' => $containerName,
                'key' => $key,
            ], $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function size($key)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $properties = $this->blobProxy->getBlobProperties($containerName, $key);

            return $properties->getProperties()->getContentLength();
        } catch (ServiceException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new FileNotFound($key);
            }

            throw StorageFailure::unexpectedFailure('size', [
                'containerName' => $containerName,
                'key' => $key,
            ], $e);
        }

    }

    /**
     * {@inheritdoc}
     */
    public function checksum($key)
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
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function delete($key)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $this->blobProxy->deleteBlob($containerName, $key);
        } catch (ServiceException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new FileNotFound($key);
            }

            throw StorageFailure::unexpectedFailure('delete', [
                'containerName' => $containerName,
                'key' => $key,
            ], $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function rename($sourceKey, $targetKey)
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
        } catch (ServiceException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new FileNotFound($sourceKey);
            }

            throw StorageFailure::unexpectedFailure('rename', [
                'sourceContainerName' => $sourceContainerName,
                'sourceKey' => $sourceKey,
                'targetContainerName' => $targetContainerName,
                'targetKey' => $targetKey,
            ], $e);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @TODO: should work like AwsS3
     */
    public function isDirectory($key)
    {
        // Windows Azure Blob Storage does not support directories
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata($key, $content)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $this->blobProxy->setBlobMetadata($containerName, $key, $content);
        } catch (ServiceException $e) {
            throw StorageFailure::unexpectedFailure('setMetadata', [
                'containerName' => $containerName,
                'key' => $key,
            ], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $properties = $this->blobProxy->getBlobProperties($containerName, $key);

            return $properties->getMetadata();
        } catch (ServiceException $e) {
            throw StorageFailure::unexpectedFailure('getMetadata', [
                'containerName' => $containerName,
                'key' => $key,
            ], $e);
        }
    }

    /**
     * Lazy initialization, automatically called when some method is called after construction.
     */
    protected function init()
    {
        if ($this->blobProxy === null) {
            $this->blobProxy = $this->blobProxyFactory->create();
        }
    }

    /**
     * Extracts the error code from a service exception.
     *
     * @param ServiceException $exception
     *
     * @return string
     */
    protected function getErrorCodeFromServiceException(ServiceException $exception)
    {
        $xml = @simplexml_load_string($exception->getResponse()->getBody());

        if ($xml && isset($xml->Code)) {
            return (string) $xml->Code;
        }

        return $exception->getErrorText();
    }

    /**
     * @param string|resource $content
     *
     * @return string
     */
    private function guessContentType($content)
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        if (is_resource($content)) {
            return $fileInfo->file(stream_get_meta_data($content)['uri']);
        }

        return $fileInfo->buffer($content);
    }

    /**
     * @param string $key
     *
     * @return array
     *
     * @throws InvalidKey
     */
    private function tokenizeKey($key)
    {
        $containerName = $this->containerName;
        if (false === $this->multiContainerMode) {
            return [$containerName, $key];
        }

        if (false === ($index = strpos($key, '/'))) {
            throw new InvalidKey(sprintf(
                'Failed to establish container name from key "%s", container name is required in multi-container mode',
                $key
            ));
        }

        $containerName = substr($key, 0, $index);
        $key = substr($key, $index + 1);

        return [$containerName, $key];
    }

    /**
     * @param string $containerName
     * @param null   $prefix
     *
     * @return array
     */
    private function fetchBlobs($containerName, $prefix = null)
    {
        $blobList = $this->blobProxy->listBlobs($containerName);

        return array_map(function (Blob $blob) use ($prefix) {
                $name = $blob->getName();

                if (null !== $prefix) {
                    $name = $prefix .'/'. $name;
                }

                return $name;
            },
            $blobList->getBlobs()
        );
    }
}
