<?php
/**
 * Created by PhpStorm.
 * User: vincent
 * Date: 7/6/16
 * Time: 2:59 PM
 */

namespace Gaufrette\Adapter;


interface ListFilesAware
{
    /**
     * Lists files beginning with pattern given
     * (no wildcard / regex matching)
     *
     * @param string $prefix
     * @return array
     */
    public function listFiles($prefix = '');
}