<?php

namespace Gaufrette\Adapter;

global $createdDirectory;

function time()
{
    return \strtotime('2012-10-10 23:10:10');
}

function file_exists($path)
{
    //fake it for ssh+ssl: protocol for SFTP testing, otherwise delegate to global
    if (strpos($path, 'ssh+ssl:') === 0) {
        return in_array($path, ['/home/l3l0/filename', '/home/somedir/filename', 'ssh+ssl://localhost/home/l3l0/filename']) ? true : false;
    }

    return \file_exists($path);
}

function extension_loaded($name)
{
    global $extensionLoaded;

    if (is_null($extensionLoaded)) {
        return true;
    }

    return $extensionLoaded;
}

function opendir($url)
{
    return true;
}

function apc_fetch($path)
{
    return sprintf('%s content', $path);
}

function apc_store($path, $content, $ttl)
{
    if ('prefix-apc-test/invalid' === $path) {
        return false;
    }

    return sprintf('%s content', $path);
}

function apc_delete($path)
{
    if ('prefix-apc-test/invalid' === $path) {
        return false;
    }

    return true;
}

function apc_exists($path)
{
    if ('prefix-apc-test/invalid' === $path) {
        return false;
    }

    return true;
}
