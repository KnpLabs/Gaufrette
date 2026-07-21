<?php

namespace Gaufrette\Adapter;

global $createdDirectory;

function time(): int
{
    return \strtotime('2012-10-10 23:10:10');
}

function file_exists(string $path)
{
    //fake it for ssh+ssl: protocol for SFTP testing, otherwise delegate to global
    if (str_starts_with($path, 'ssh+ssl:')) {
        return in_array($path, ['/home/l3l0/filename', '/home/somedir/filename', 'ssh+ssl://localhost/home/l3l0/filename']) ? true : false;
    }

    return \file_exists($path);
}

function extension_loaded()
{
    global $extensionLoaded;

    if (is_null($extensionLoaded)) {
        return true;
    }

    return $extensionLoaded;
}

function opendir(string $url): bool
{
    return true;
}

function apc_fetch(string $path): string
{
    return sprintf('%s content', $path);
}

function apc_store(string $path, mixed $content, int $ttl): false|string
{
    if ('prefix-apc-test/invalid' === $path) {
        return false;
    }

    return sprintf('%s content', $path);
}

function apc_delete(string $path): bool
{
    if ('prefix-apc-test/invalid' === $path) {
        return false;
    }

    return true;
}

function apc_exists(mixed $path): bool
{
    if ('prefix-apc-test/invalid' === $path) {
        return false;
    }

    return true;
}
