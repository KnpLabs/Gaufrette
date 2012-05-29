<?php

namespace Gaufrette\Adapter;

use Gaufrette\Util;
use Gaufrette\Exception;

/**
 * Dropbox adapter
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Dropbox extends Base
{
    protected $client;

    /**
     * Constructor
     *
     * @param \Dropbox_API $client The Dropbox API client
     */
    public function __construct(\Dropbox_API $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        try {
            return $this->client->getFile($key);
        } catch (\Dropbox_Exception_NotFound $e) {
            throw new Exception\FileNotFound($key, 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $resource = tmpfile();
        fwrite($resource, $content);
        fseek($resource, 0);

        try {
            $this->client->putFile($key, $resource);
        } catch (\Exception $e) {
            fclose($resource);
            throw $e;
        }

        fclose($resource);

        return Util\Size::fromContent($content);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        try {
            $this->client->delete($key);
        } catch (\Dropbox_Exception_NotFound $e) {
            throw new Exception\FileNotFound($key, 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        try {
            $this->client->move($sourceKey, $targetKey);
        } catch (\Dropbox_Exception_NotFound $e) {
            throw new Exception\FileNotFound($sourceKey, 0, $e);
        } catch (\Dropbox_Exception_Forbidden $e) {
            // TODO find a better way to be sure it's because the target file
            //      exists
            if ($this->exists($targetKey)) {
                throw new Exception\UnexpectedFile($targetKey);
            }

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        return Util\Checksum::fromContent($this->read($key));
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $metadata = $this->getMetadata($key);

        return strtotime($metadata['modified']);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $metadata = $this->client->getMetaData('/', true);
        $files    = isset($metadata['contents']) ? $metadata['contents'] : array();

        return array_map(
            function($value) {
                return ltrim($value['path'], '/');
            },
            $files
        );
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        try {
            $this->getMetadata($key);

            return true;
        } catch (Exception\FileNotFound $e) {
            return false;
        }
    }

    private function getMetadata($key)
    {
        try {
            $metadata = $this->client->getMetaData($key, false);
        } catch (\Dropbox_Exception_NotFound $e) {
            throw new Exception\FileNotFound($key, 0, $e);
        }

        // TODO find a way to exclude deleted files
        if (isset($metadata['is_deleted']) && $metadata['is_deleted']) {
            throw new Exception\FileNotFound($key);
        }

        return $metadata;
    }
}
