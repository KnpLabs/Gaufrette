<?php

namespace Gaufrette\Functional\Adapter\OpenStack;

use Gaufrette\Adapter\OpenStack as OpenStackAdapter;
use Gaufrette\Filesystem;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use OpenStack\Identity\v2\Service as IdentityService;
use OpenStack\OpenStack;

class IdentityV2Test extends OpenStackTestCase
{
    public function setUp()
    {
        $username = getenv('RACKSPACE_USERNAME') ?: '';
        $password = getenv('RACKSPACE_PASSWORD') ?: '';
        $tenantId = getenv('RACKSPACE_TENANT_ID') ?: '';
        $region = getenv('RACKSPACE_REGION') ?: '';

        if (empty($username) || empty($password) || empty($tenantId) || empty($region)) {
            $this->markTestSkipped('Either RACKSPACE_USERNAME, RACKSPACE_PASSWORD, RACKSPACE_TENANT_ID and/or RACKSPACE_REGION env vars are missing.');
        }

        $authUrl = 'https://identity.api.rackspacecloud.com/v2.0/';

        /*
         * Rackspace uses OpenStack Identity v2
         * @see https://github.com/php-opencloud/openstack/issues/127
         */
        $this->container = uniqid('gaufretteci');
        $this->objectStore = (new OpenStack([
                'username' => $username,
                'password' => $password,
                'tenantId' => $tenantId,
                'authUrl' => $authUrl,
                'region' => $region,
                'identityService' => IdentityService::factory(
                    new Client([
                        'base_uri' => $authUrl,
                        'handler' => HandlerStack::create(),
                    ])
                ),
            ]))
            ->objectStoreV1([
                'catalogName' => 'cloudFiles',
            ]);

        $this->objectStore->createContainer([
            'name' => $this->container,
        ]);
        $adapter = new OpenStackAdapter($this->objectStore, $this->container);
        $this->filesystem = new Filesystem($adapter);
    }
}
