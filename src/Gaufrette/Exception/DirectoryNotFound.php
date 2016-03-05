<?php

namespace Gaufrette\Exception;

use Gaufrette\Exception;

/**
 * Exception to be thrown when directory doesn't exist while it was expected..
 *
 * @author Andrew Kovalyov <andrew.kovalyoff@gmail.com>
 */
class DirectoryNotFound extends Base
{
    public function __construct($key, $code = 0, \Exception $previous = null)
    {
        $this->key = $key;

        parent::__construct(
            sprintf('The directory %s does not exist.', $key),
            $code,
            $previous
        );
    }
}
