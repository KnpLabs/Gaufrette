<?php

namespace Gaufrette\Adapter\Phpseclib\Tests;

use Gaufrette\Adapter\Phpseclib\Sftp;
use Gaufrette\Filesystem;
use Gaufrette\Functional\Adapter\FunctionalTestCase;

class SftpTest extends FunctionalTestCase
{
    /** @var string */
    private $directory;

    /** @var \phpseclib\Net\SFTP */
    private $sftp;

    /** @var bool */
    private $skipped = false;

    public function setUp()
    {
        $this->skipped = getenv('SFTP_HOST') === false;

        if ($this->skipped) {
            $this->markTestSkipped('No SFTP host defined.');
            return;
        }

        $this->directory = getenv('SFTP_BASE_DIR') . '/' . uniqid();
        $this->sftp = new \phpseclib\Net\SFTP(getenv('SFTP_HOST'), getenv('SFTP_PORT'));
        $this->sftp->login(getenv('SFTP_USER'), getenv('SFTP_PASS'));

        if (!$this->sftp->mkdir($this->directory, 0750, true)) {
            throw new \RuntimeException(sprintf(
                'Unable to create temporary directory "%s". Last SFTP error: %s',
                $this->directory,
                $this->sftp->getLastSFTPError()
            ));
        }

        $adapter = new Sftp($this->sftp, $this->directory, true);
        $this->filesystem = new Filesystem($adapter);
    }

    public function tearDown()
    {
        if ($this->skipped) {
            return;
        }

        $this->sftp->delete($this->directory, true);
    }
}
