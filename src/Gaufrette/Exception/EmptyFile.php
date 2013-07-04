<?php

namespace Gaufrette\Exception;

use Gaufrette\Exception;

/**
 * Exception to be thrown when a file object content is not properly populated for writing
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
class EmptyFile extends \InvalidArgumentException implements Exception
{
    private $key;

    public function __construct($key, $code = 0, \Exception $previous = null)
    {
        $this->key = $key;

        parent::__construct(
            sprintf('The content of file %s is empty.', $key),
            $code,
            $previous
        );
    }

    public function getKey()
    {
        return $this->key;
    }
}
