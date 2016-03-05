<?php

namespace Gaufrette\Exception;

use Gaufrette\Exception;

/**
 * Exception to be thrown when a file can't be opened for reading.
 *
 * @author Andrew Kovalyov <andrew.kovalyoff@gmail.com>
 */
class CannotRead extends Base
{
    public function __construct($key, $code = 0, \Exception $previous = null)
    {
        $this->key = $key;

        parent::__construct(
            sprintf('The file %s can\'t be read.', $key),
            $code,
            $previous
        );
    }
}
