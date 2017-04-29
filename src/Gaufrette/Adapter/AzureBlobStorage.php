<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Util;
use Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactoryInterface;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\DeleteContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

/**
 * Microsoft Azure Blob Storage adapter.
 *
 * @author Luciano Mammino <lmammino@oryzone.com>
 * @author Paweł Czyżewski <pawel.czyzewski@enginewerk.com>
 */
class AzureBlobStorage implements Adapter,
                                  MetadataSupporter
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
     * @throws \RuntimeException if cannot create the container
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
     * @param string                 $containerName
     * @param DeleteContainerOptions $options
     *
     * @throws \RuntimeException if cannot delete the container
     */
    public function deleteContainer($containerName, DeleteContainerOptions $options = null)
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
     * @throws \RuntimeException
     */
    public function read($key)
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
     * @throws \RuntimeException
     */
    public function write($key, $content)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        $options = new CreateBlobOptions();

        if ($this->detectContentType) {
            $contentType = $this->guessContentType($content);

            $options->setBlobContentType($contentType);
        }

        try {
            $this->createContainer($containerName);

            $this->blobProxy->createBlockBlob($containerName, $key, $content, $options);

            if (is_resource($content)) {
                return Util\Size::fromResource($content);
            }

            return Util\Size::fromContent($content);
        } catch (ServiceException $e) {
            $this->failIfContainerNotFound($e, sprintf('write content for key "%s"', $key), $containerName);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function exists($key)
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
            $this->failIfContainerNotFound($e, 'check if key exists', $containerName);
            $errorCode = $this->getErrorCodeFromServiceException($e);

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
     * @throws \RuntimeException
     */
    public function keys()
    {
        $this->init();
        // list($containerName, $key) = $this->tokenizeKey($key);

        try {
            $blobList = $this->blobProxy->listBlobs($this->containerName);

            return array_map(
                function (Blob $blob) {
                    return $blob->getName();
                },
                $blobList->getBlobs()
            );
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
     * @throws \RuntimeException
     */
    public function mtime($key)
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
     * @throws \RuntimeException
     */
    public function delete($key)
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
     * @throws \RuntimeException
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->init();

        list($sourceContainerName, $sourceKey) = $this->tokenizeKey($sourceKey);
        list($targetContainerName, $targetKey) = $this->tokenizeKey($targetKey);

        try {
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
    public function isDirectory($key)
    {
        // Windows Azure Blob Storage does not support directories
        return false;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function setMetadata($key, $content)
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
     * @throws \RuntimeException
     */
    public function getMetadata($key)
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
    protected function init()
    {
        if ($this->blobProxy === null) {
            $this->blobProxy = $this->blobProxyFactory->create();
        }
    }

    /**
     * Throws a runtime exception if a give ServiceException derived from a "container not found" error.
     *
     * @param ServiceException $exception
     * @param string           $action
     *
     * @throws \RuntimeException
     */
    protected function failIfContainerNotFound(ServiceException $exception, $action, $containerName)
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
     *
     * @param ServiceException $exception
     *
     * @return string
     */
    protected function getErrorCodeFromServiceException(ServiceException $exception)
    {
        $errorText = $exception->getErrorText();
        switch ($errorText) {
            case 'The specified container already exists.':
                return self::ERROR_CONTAINER_ALREADY_EXISTS;
                break;
        }
        return $errorText;
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
     * @throws \InvalidArgumentException
     */
    private function tokenizeKey($key)
    {
        $containerName = $this->containerName;
        if (true === $this->multiContainerMode) {
            if (false === ($index = strpos($key, '/'))) {
                throw new \InvalidArgumentException('No /');
            }
            $containerName = substr($key, 0, $index);
            $key = substr($key, $index + 1);
        }
        return array($containerName, $key);
    }
}
