<?php

namespace Gaufrette\Exception;

use Gaufrette\Exception;

/**
 * Exception to be thrown when an unexpected file exists.
 *
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class UnexpectedFile extends \RuntimeException implements Exception
{
    private string $key;

    public function __construct(string $key, int $code = 0, \Exception $previous = null)
    {
        parent::__construct(
            sprintf('The file "%s" was not supposed to exist.', $key),
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
