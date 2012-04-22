<?php

namespace Gaufrette\Adapter;

class FtpTest extends FunctionalTestCase
{
    public function setUp()
    {
        if (!isset($_SERVER['FTP_HOST'])) {
            return $this->markTestSkipped('FTP server not configured.');
        }

        $arguments = array(
            'directory' => null,
            'host'      => null,
            'username'  => null,
            'password'  => null,
            'port'      => 21,
            'passive'   => false,
            'create'    => false,
            'mode'      => FTP_ASCII,
        );

        foreach ($arguments as $key => $value) {
            $serverKey = sprintf('FTP_%s', strtoupper($key));
            if (isset($_SERVER[$serverKey])) {
                $arguments[$key] = is_bool($value)
                    ? (Boolean) $_SERVER[$serverKey]
                    : $_SERVER[$serverKey];
            }
        }

        $this->adapter = new Ftp(
            $arguments['directory'],
            $arguments['host'],
            $arguments['username'],
            $arguments['password'],
            $arguments['port'],
            $arguments['passive'],
            $arguments['create'],
            $arguments['mode']
        );
    }

    public function tearDown()
    {
        if (null === $this->adapter) {
            return;
        }

        foreach ($this->adapter->keys() as $key) {
            $this->adapter->delete($key);
        }
    }
}
