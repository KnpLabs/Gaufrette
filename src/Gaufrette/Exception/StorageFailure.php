<?php

namespace Gaufrette\Exception;

use Gaufrette\Exception;

/**
 * Exception thrown when an unexpected error happened at the storage level (or its underlying sdk).
 *
 * @author Albin Kerouanton <albin.kerouanton@knplabs.com>
 */
class StorageFailure extends \RuntimeException implements Exception
{
    /**
     * Instantiate a new StorageFailure exception for a particular adapter action.
     *
     * @param string          $action    The adapter action (e.g read, write, listKeys, ...) that throw the exception.
     * @param array           $args      Arguments used by the action (like the read key).
     * @param \Exception|null $previous  Previous exception, if any was thrown (like exception from AWS sdk).
     *
     * @return StorageFailure
     */
    public static function unexpectedFailure($action, array $args, \Exception $previous = null)
    {
        $args = array_map(function ($k, $v) {
            $v = is_string($v) ? '"'.$v.'"' : $v;

            return "{$k}: {$v}";
        }, array_keys($args), $args);

        return new self(
            sprintf('An unexpected error happened during %s (%s).', $action, implode(', ', $args)),
            0,
            $previous
        );
    }
}
