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
        $args = implode(', ', self::normalizeArgs($args));

        return new self(
            sprintf('An unexpected error happened during "%s" (%s).', $action, $args),
            0,
            $previous
        );
    }

    /**
     * Instantiate a new StorageFailure exception with a error message.
     *
     * @param string $action The adapter action (e.g read, write, listKeys, ...) that throw the exception.
     * @param array  $args   Arguments used by the action (like the read key).
     * @param string $error  Error message received by the adapter.
     *
     * @return StorageFailure
     */
    public static function fromErrorMessage($action, array $args, $error)
    {
        $message = sprintf(
            'An unexpected error happened during "%s" (%s).',
            $action,
            implode(', ', self::normalizeArgs($args))
        );

        if (!empty($error)) {
            $message .= ' Error: ' . $error;
        }

        return new self($message);
    }

    /**
     * @param array $args Key-value array of arguments used by the adapter.
     *
     * @return array Numerically-indexed array of strings representing key-value pairs.
     */
    private static function normalizeArgs(array $args)
    {
        return array_map(function ($k, $v) {
            $v = var_export($v, true);

            return "{$k}: {$v}";
        }, array_keys($args), $args);
    }
}
