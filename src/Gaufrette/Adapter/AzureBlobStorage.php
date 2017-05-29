<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Util;
use Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactoryInterface;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\Container;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\DeleteContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Common\ServiceException;
use Psr\Http\Message\ResponseInterface;

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
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
     */
    public function write($key, $content)
    {
        $this->init();
        list($containerName, $key) = $this->tokenizeKey($key);

        $options = new CreateBlobOptions();

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
            $this->failIfContainerNotFound($e, sprintf('write content for key "%s"', $key), $containerName);

            return false;
        }
        if (is_resource($content)) {
            return Util\Size::fromResource($content);
        }

        return Util\Size::fromContent($content);
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
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
     * @throws \RuntimeException
     */
    public function keys()
    {
        $this->init();

        try {
            if ($this->multiContainerMode) {
                $containersList = $this->blobProxy->listContainers();
                return call_user_func_array('array_merge', array_map(
                    function(Container $container) {
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
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
     * @param string           $containerName
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
        if (method_exists($exception, 'getErrorReason')) {
            $xml = @simplexml_load_string($exception->getErrorReason());

            if ($xml && isset($xml->Code)) {
                return (string) $xml->Code;
            }

            return $exception->getErrorReason();
        } else {
            /** @var ResponseInterface $response */
            $response = $exception->getResponse();
            return static::parseErrorCode($response);
        }
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
     * @param string $containerName
     * @param null   $prefix
     *
     * @return array
     */
    private function fetchBlobs($containerName, $prefix = null)
    {
        $blobList = $this->blobProxy->listBlobs($containerName);
        return array_map(
            function (Blob $blob) use ($prefix) {
                $name = $blob->getName();
                if (null !== $prefix) {
                    $name = $prefix .'/'. $name;
                }
                return $name;
            },
            $blobList->getBlobs()
        );
    }

    /**
     * Error message to be parsed.
     *
     * @param  ResponseInterface $response The response with a response body.
     *
     * @return string
     */
    protected static function parseErrorCode(ResponseInterface $response)
    {
        $errorCode = $response->getReasonPhrase();

        //try to parse using xml serializer, if failed, return the whole body
        //as the error message.
        try {
            $body = new \SimpleXMLElement($response->getBody());
            $data = static::xmlToArray($body);

            if (array_key_exists('Code', $data)) {
                $errorCode = $data['Code'];
            }
        } catch (\Exception $e) {
        }

        return $errorCode;
    }

    /**
     * Converts a SimpleXML object to an Array recursively
     * ensuring all sub-elements are arrays as well.
     *
     * @param string $sxml The SimpleXML object.
     * @param array  $arr  The array into which to store results.
     *
     * @return array
     */
    private static function xmlToArray($sxml, array $arr = null)
    {
        foreach ((array) $sxml as $key => $value) {
            if (is_object($value) || (is_array($value))) {
                $arr[$key] = static::xmlToArray($value);
            } else {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }
}
