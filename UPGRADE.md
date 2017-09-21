1.0
===

**Gaufrette\Adapter\OpenStackCloudFiles\ObjectStoreFactory:**
* This factory has been removed

**Gaufrette\Adapter\OpenCloud:**
* This adapter has been removed and is now replaced by
`Gaufrette\Adapter\OpenStack`. Additionally, the
[`rackspace/php-opencloud`](https://github.com/rackspace/php-opencloud) SDK
was replaced by the
[`php-opencloud/openstack`](https://github.com/php-opencloud/openstack) SDK.

**Gaufrette\Adapter\SafeLocal:**
* This adapter has been removed and will be superseded.

**Gaufrette\Adapter\Local:**
*  The base directory is now automatically created if it does not exist. Thus, the constructor signature has changed from `__construct($directory, $create = false, $mode = 0777)` to `__construct($directory, $mode = 0777)`.
