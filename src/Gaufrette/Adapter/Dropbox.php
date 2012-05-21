<?php

namespace Gaufrette\Adapter;

/**
 * Dropbox adapter
 *
 * @package Gaufrette
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class Dropbox extends Base
{
    /**
     * @var \Dropbox_API
     */
    protected $dropbox;

    /**
     * Constructor
     *
     * @param \Dropbox_API $dropbox
     */
    public function __construct(\Dropbox_API $dropbox)
    {
        $this->dropbox = $dropbox;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        return $this->dropbox->getFile($key);
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $resource = tmpfile();
        fwrite($resource, $content);
        fseek($resource, 0);

        if (!$this->dropbox->putFile($key, $resource)) {
            fclose($resource);
            throw new \RuntimeException(sprintf('Unable to write file %s', $key));
        }

        fclose($resource);

        return strlen($content);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->dropbox->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function rename($key, $new)
    {
        $this->dropbox->move($key, $new);
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        return md5($this->dropbox->getFile($key));
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $info = $this->dropbox->getMetaData($key);
        return strtotime($info['modified']);
    }

    /**
     * Returns an array of all keys matching the specified pattern
     *
     * @return array
     */
    public function keys()
    {
        $metadata = $this->dropbox->getMetaData('/', true);
        $files    = isset($metadata['contents']) ? $metadata['contents'] : array();

        return array_map(function($value) {
            return ltrim($value['path'], '/');
        }, $files);
    }

    /**
     * Indicates whether the file exists
     *
     * @param  string $key
     *
     * @return boolean
     */
    public function exists($key)
    {
        $results = $this->dropbox->search($key);
        return !empty($key);
    }


}
