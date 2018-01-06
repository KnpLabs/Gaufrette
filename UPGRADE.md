1.0
===

**Gaufrette\Adapter\SafeLocal:**
* This adapter has been removed and will be superseded.

**Gaufrette\Adapter\Local:**
*  The base directory is now automatically created if it does not exist. Thus, the constructor signature has changed from `__construct($directory, $create = false, $mode = 0777)` to `__construct($directory, $mode = 0777)`.
