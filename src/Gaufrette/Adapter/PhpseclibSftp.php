<?php

namespace Gaufrette\Adapter;

@trigger_error('The '.__NAMESPACE__.'\PhpseclibSftp is deprecated since version 0.4. Use Gaufrette\Adapter\Phpseclib\Sftp instead.', E_USER_DEPRECATED);

use Gaufrette\Adapter;
use phpseclib\Net\SFTP as SecLibSFTP;

/**
 * @deprecated 0.4 This adapter is deprecated since version 0.4. Use Gaufrette\Adapters\Phpseclib\Sftp instead.
 */
class PhpseclibSftp extends Adapter\Phpseclib\Sftp
{
}
