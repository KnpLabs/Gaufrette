<?php

namespace Gaufrette\Functional\Adapter\OpenStack;

use Gaufrette\Adapter\OpenStack as OpenStackAdapter;
use Gaufrette\Filesystem;
use OpenStack\OpenStack;

class IdentityV3Test extends OpenStackTestCase
{
    public function setUp()
    {
        $userId = getenv('IBMCLOUD_USERID') ?: '';
        $password = getenv('IBMCLOUD_PASSWORD') ?: '';
        $region = getenv('IBMCLOUD_REGION') ?: '';

        if (empty($userId) || empty($password) || empty($region)) {
            $this->markTestSkipped('Either IBMCLOUD_USERID, IBMCLOUD_PASSWORD, and/or IBMCLOUD_REGION env vars are missing.');
        }

        $authUrl = 'https://identity.open.softlayer.com/v3/';

        $this->container = uniqid('gaufretteci');
        $this->objectStore = (new OpenStack([
                'user' => [
                    'id' => $userId,
                    'password' => $password,
                ],
                'authUrl' => $authUrl,
                'region' => $region,
            ]))
            ->objectStoreV1();

        $this->objectStore->createContainer([
            'name' => $this->container,
        ]);
        $adapter = new OpenStackAdapter($this->objectStore, $this->container);
        $this->filesystem = new Filesystem($adapter);
    }
}
