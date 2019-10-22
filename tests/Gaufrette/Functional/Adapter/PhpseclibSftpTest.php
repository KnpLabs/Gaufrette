<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\PhpseclibSftp;
use Gaufrette\Filesystem;
use phpseclib\Net\SFTP;

class PhpseclibSftpTest extends FunctionalTestCase
{
    /** @var SFTP */
    private $sftp;

    /** @var string */
    private $baseDir;

    protected function setUp()
    {
        $host = getenv('SFTP_HOST');
        $port = getenv('SFTP_PORT') ?: 22;
        $user = getenv('SFTP_USER');
        $password = getenv('SFTP_PASSWORD');
        $baseDir = getenv('SFTP_BASE_DIR');

        if ($host === false || $user === false || $password === false || $baseDir === false) {
            $this->markTestSkipped('Either SFTP_HOST, SFTP_USER, SFTP_PASSWORD and/or SFTP_BASE_DIR env variables are not defined.');
        }

        $this->baseDir = rtrim($baseDir, '/') . '/' . uniqid();

        $this->sftp = new SFTP($host, $port);
        $this->sftp->login($user, $password);

        $this->filesystem = new Filesystem(new PhpseclibSftp($this->sftp, $this->baseDir, true));
    }

    protected function tearDown()
    {
        if (!isset($this->sftp)) {
            return;
        }

        $this->sftp->rmdir($this->baseDir);
    }
}
