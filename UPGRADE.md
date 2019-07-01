1.0
===

**Gaufrette\Adapter\AzureblobStorage:**
As container management is out of Gaufrette scope (see #618), this adapter has
the following BC breaks :
* The `createContainer` public method has been removed.
* The `deleteContainer` public method has been removed.
* The `getCreateContainerOptions` public method has been removed.
* The `setCreateContainerOptions` public method has been removed.
* Drop support for [multi continer mode](https://github.com/KnpLabs/Gaufrette/blob/b488cf8f595c3c7a35005f72b60692e14c69398c/doc/adapters/azure-blob-storage.md#multi-container-mode).
* The constructor's `create` parameter has been removed.
* The constructor's `containerName` parameter is now mandatory (string).

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
