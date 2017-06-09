<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Util;
use Gaufrette\Exception;
use Dropbox_API as DropboxApi;
use Dropbox_Exception_NotFound as DropboxNotFoundException;

@trigger_error('The '.__NAMESPACE__.'\Dropbox adapter is deprecated since version 0.4 and will be removed in 1.0. You can move to our Flysystem adapter and use their Dropbox adapter if needed.', E_USER_DEPRECATED);

/**
 * Dropbox adapter.
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 *
 * @deprecated The Dropbox adapter is deprecated since version 0.4 and will be removed in 1.0.
 */
class Dropbox implements Adapter
{
    protected $client;

    /**
     * @param \Dropbox_API $client The Dropbox API client
     */
    public function __construct(DropboxApi $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Dropbox_Exception_Forbidden
     * @throws \Dropbox_Exception_OverQuota
     * @throws \OAuthException
     */
    public function read($key)
    {
        try {
            return $this->client->getFile($key);
        } catch (DropboxNotFoundException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        try {
            $metadata = $this->getDropboxMetadata($key);
        } catch (Exception\FileNotFound $e) {
            return false;
        }

        return (boolean) isset($metadata['is_dir']) ? $metadata['is_dir'] : false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Dropbox_Exception
     */
    public function write($key, $content)
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
     * {@inheritdoc}
     */
    public function delete($key)
    {
        try {
            $this->client->delete($key);
        } catch (DropboxNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        try {
            $this->client->move($sourceKey, $targetKey);
        } catch (DropboxNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        try {
            $metadata = $this->getDropboxMetadata($key);
        } catch (Exception\FileNotFound $e) {
            return false;
        }

        return strtotime($metadata['modified']);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        $metadata = $this->client->getMetaData('/', true);
        if (!isset($metadata['contents'])) {
            return array();
        }

        $keys = array();
        foreach ($metadata['contents'] as $value) {
            $file = ltrim($value['path'], '/');
            $keys[] = $file;
            if ('.' !== $dirname = \Gaufrette\Util\Path::dirname($file)) {
                $keys[] = $dirname;
            }
        }
        sort($keys);

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        try {
            $this->getDropboxMetadata($key);

            return true;
        } catch (Exception\FileNotFound $e) {
            return false;
        }
    }

    private function getDropboxMetadata($key)
    {
        try {
            $metadata = $this->client->getMetaData($key, false);
        } catch (DropboxNotFoundException $e) {
            throw new Exception\FileNotFound($key, 0, $e);
        }

        // TODO find a way to exclude deleted files
        if (isset($metadata['is_deleted']) && $metadata['is_deleted']) {
            throw new Exception\FileNotFound($key);
        }

        return $metadata;
    }
}
