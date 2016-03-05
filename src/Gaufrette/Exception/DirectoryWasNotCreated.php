<?php

namespace Gaufrette\Exception;

use Gaufrette\Exception;

/**
 * Exception to be thrown when directory could not be created.
 *
 * @author Andrew Kovalyov <andrew.kovalyoff@gmail.com>
 */
class DirectoryWasNotCreated extends Base
{
    public function __construct($key, $code = 0, \Exception $previous = null)
    {
        $this->key = $key;

        parent::__construct(
            sprintf('The directory \'%s\' could not be created.', $key),
            $code,
            $previous
        );
    }
}
