<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\Ftp;
use Gaufrette\Filesystem;

class FtpTest extends FunctionalTestCase
{
    protected function setUp()
    {
        $host = getenv('FTP_HOST');
        $port = getenv('FTP_PORT');
        $user = getenv('FTP_USER');
        $password = getenv('FTP_PASSWORD');
        $baseDir = getenv('FTP_BASE_DIR');

        if ($user === false || $password === false || $host === false || $baseDir === false) {
            $this->markTestSkipped('Either FTP_HOST, FTP_USER, FTP_PASSWORD and/or FTP_BASE_DIR env variables are not defined.');
        }

        $adapter = new Ftp($baseDir, $host, ['port' => $port, 'username' => $user, 'password' => $password, 'passive' => true, 'create' => true]);
        $this->filesystem = new Filesystem($adapter);
    }

    protected function tearDown()
    {
        if (null === $this->filesystem) {
            return;
        }

        $adapter = $this->filesystem->getAdapter();

        foreach ($adapter->keys() as $key) {
            if (!$adapter->isDirectory($key)) {
                $adapter->delete($key);
            }
        }

        $keys = $adapter->keys();
        rsort($keys);
        foreach ($keys as $key) {
            $adapter->delete($key);
        }

        $adapter->close();
    }
}
