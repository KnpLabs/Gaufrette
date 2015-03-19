<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Util;
use Gaufrette\Exception;
use Gaufrette\Adapter\ListKeysAware;
use \Dropbox_API as DropboxApi;
use \Dropbox_Exception_NotFound as DropboxNotFoundException;

/**
 * Dropbox adapter
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 * @author Igor Mukhin <igor.mukhin@gmail.com>
 */
class Dropbox implements Adapter,
                         ListKeysAware
{
    protected $client;
    protected $path;
    protected $limit;

    /**
     * Constructor
     *
     * @param \Dropbox_API $client The Dropbox API client
     * @param $path Path inside dropbox to work with
     * @param $limit Limit that will be passed to getMetaData
     */
    public function __construct(DropboxApi $client, $path = '', $limit = 10000)
    {
        $this->client = $client;
        $this->path = trim($path, '/');

        if ($limit > 25000) {
            throw new Exception(sprintf(
                "You're trying to set %s to limit, but limit can't be greather than 25000",
                $limit
            ));
        }
        $this->limit = $limit;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Dropbox_Exception_Forbidden
     * @throws \Dropbox_Exception_OverQuota
     * @throws \OAuthException
     */
    public function read($key)
    {
        try {
            $key = $this->computePath($key);
            return $this->client->getFile($key);
        } catch (DropboxNotFoundException $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     *
     * @throws \Dropbox_Exception
     */
    public function write($key, $content)
    {
        $resource = tmpfile();
        fwrite($resource, $content);
        fseek($resource, 0);

        try {
            $key = $this->computePath($key);
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
            $key = $this->computePath($key);
            $this->client->delete($key);
        } catch (DropboxNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        try {
            $sourceKey = $this->computePath($sourceKey);
            $targetKey = $this->computePath($targetKey);
            $this->client->move($sourceKey, $targetKey);
        } catch (DropboxNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function keys()
    {
        $metadata = $this->client->getMetaData($this->path . '/', true, null, $this->limit);
        if (! isset($metadata['contents'])) {
            return array();
        }

        $keys = array();
        foreach ($metadata['contents'] as $value) {
            $file = ltrim($value['path'], '/');
            $key = $this->cutPathFromKey($file);

            if (empty($key)) continue;

            $keys[] = $key;

            // $dirname = dirname($key);
            // if ('.' !== $dirname && !in_array($dirname, $keys['dirs'])) {
            //     $keys[] = $dirname;
            // }
        }
        sort($keys);

        return $keys;
    }

    /**
     * Implement listKeys because otherwise getMetaData extra called
     * for each key (n+1) via isDirectory function
     */
    function listKeys($prefix = '/')
    {
        $prefix = '/' . ltrim($prefix, '/');
        $metadata = $this->client->getMetaData($this->path . $prefix, true, null, $this->limit);

        if (! isset($metadata['contents'])) {
            return array();
        }

        $keys = array(
            'keys'=>array(),
            'dirs'=>array()
        );

        foreach ($metadata['contents'] as $value) {
            $file = ltrim($value['path'], '/');
            $key = $this->cutPathFromKey($file);

            if (empty($key)) continue;

            if ($value['is_dir']) {
                if ('.' !== $key && !in_array($key, $keys['dirs'])) {
                    $keys['dirs'][] = $key;
                }
            } else {
                $keys['keys'][] = $key;
            }
        }
        sort($keys['keys']);
        sort($keys['dirs']);

        return $keys;
    }

    /**
     * {@inheritDoc}
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
        $key = $this->computePath($key);
        try {
            $metadata = $this->client->getMetaData($key, false, null, $this->limit);
        } catch (DropboxNotFoundException $e) {
            throw new Exception\FileNotFound($key, 0, $e);
        }

        // TODO find a way to exclude deleted files
        if (isset($metadata['is_deleted']) && $metadata['is_deleted']) {
            throw new Exception\FileNotFound($key);
        }

        return $metadata;
    }

    /**
     *
     */
    protected function computePath($key)
    {
        if (empty($this->path)) {
            return $key;
        }

        return sprintf('%s/%s', $this->path, $key);
    }

    /**
     *
     */
    protected function cutPathFromKey ($key)
    {
        $path = $this->path . '/';
        if (substr($key, 0, strlen($path)) == $path) {
            $key = substr($key, strlen($path));
        }
        return $key;
    }
}
