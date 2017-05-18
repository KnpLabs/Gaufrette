<?php

namespace Gaufrette\Functional;

class LocalDirectoryDeletor
{
    public static function deleteDirectory($directory)
    {
        if ($directory === '/') {
            throw new \InvalidArgumentException('Deleting "/" is disallowed.');
        }

        if (file_exists($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $directory,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir(strval($item));
                } else {
                    unlink(strval($item));
                }
            }
        }
    }
}
