<?php
/**
 * Created by PhpStorm.
 * User: cwarner
 * Date: 10/2/13
 * Time: 8:54 AM
 */
namespace Gaufrette\Unit;

use Gaufrette\Adapter\OpenCloud;

class OpenCloudTest extends BaseOpenCloudTestCase
{
    protected function buildOpenCloudClass($objectStore, $createContainer = false, $detectContentType = true)
    {
        return new OpenCloud($objectStore, $createContainer, $detectContentType);
    }


}
