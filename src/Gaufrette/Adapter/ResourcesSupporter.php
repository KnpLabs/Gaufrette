<?php
namespace Gaufrette\Adapter;

/**
 * Interface for storing the resource information
 *
 * @author  Lech Buszczynski <lecho@phatcat.eu>
 */
interface ResourcesSupporter
{
    /**
     * @param   string              $key
     * @param   array               $data
     */
    public function setResources($key, $data);

    /**
     * @param   string              $key
     * @return  array               Array of resource values.
     */
    public function getResources($key);
    
    /**
     * @param   string              $key
     * @return  array|string|null   Array or string depending on resource structure. Null if resource does not exist.
     */
    public function getResourceByName($key, $resourceName);
}