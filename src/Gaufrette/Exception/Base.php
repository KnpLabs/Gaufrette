<?php

namespace Gaufrette\Exception;

use Gaufrette\Exception;

/**
 * Base exception class.
 *
 * @author Andrew Kovalyov <andrew.kovalyoff@gmail.com>
 */
abstract class Base extends \RuntimeException implements Exception
{
    protected $key;

    public function getKey()
    {
        return $this->key;
    }
}
