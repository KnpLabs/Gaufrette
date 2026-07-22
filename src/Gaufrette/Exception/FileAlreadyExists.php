<?php

namespace Gaufrette\Exception;

use Gaufrette\Exception;

/**
 * Exception to be thrown when a file already exists.
 *
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class FileAlreadyExists extends \RuntimeException implements Exception
{
    private string $key;

    public function __construct(string $key, int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct(
            sprintf('The file %s already exists and can not be overwritten.', $key),
            $code,
            $previous
        );

        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
