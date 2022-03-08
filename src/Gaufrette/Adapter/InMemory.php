<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Util;

/**
 * In memory adapter.
 *
 * Stores some files in memory for test purposes
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class InMemory implements Adapter, MimeTypeProvider
{
    protected $files = [];

    /**
     * @param array $files An array of files
     */
    public function __construct(array $files = [])
    {
        $this->setFiles($files);
    }

    /**
     * Defines the files.
     *
     * @param array $files An array of files
     */
    public function setFiles(array $files)
    {
        $this->files = [];
        foreach ($files as $key => $file) {
            if (!is_array($file)) {
                $file = ['content' => $file];
            }

            $file = array_merge([
                'content' => null,
                'mtime' => null,
            ], $file);

            $this->setFile($key, $file['content'], $file['mtime']);
        }
    }

    /**
     * Defines a file.
     *
     * @param string $key     The key
     * @param string $content The content
     * @param int    $mtime   The last modified time (automatically set to now if NULL)
     */
    public function setFile($key, $content = null, $mtime = null)
    {
        if (null === $mtime) {
            $mtime = time();
        }

        $this->files[$key] = [
            'content' => (string) $content,
            'mtime' => (integer) $mtime,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        return $this->files[$key]['content'];
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $content = $this->read($sourceKey);
        $this->delete($sourceKey);

        return (boolean) $this->write($targetKey, $content);
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $this->files[$key]['content'] = $content;
        $this->files[$key]['mtime'] = time();

        return Util\Size::fromContent($content);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        return array_key_exists($key, $this->files);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return array_keys($this->files);
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        return isset($this->files[$key]['mtime']) ? $this->files[$key]['mtime'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->files[$key]);
        clearstatcache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType($key)
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        return $fileInfo->buffer($this->files[$key]['content']);
    }
}
