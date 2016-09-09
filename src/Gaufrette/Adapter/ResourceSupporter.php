<?php
namespace Gaufrette\Adapter;

/**
 * Interface for storing the resource information
 *
 * @author  Lech Buszczynski <lecho@phatcat.eu>
 */
interface ResourceSupporter
{
    /**
     * @param   string  $key
     * @param   array   $data
     */
    public function setResource($key, $data);

    /**
     * @param   string          $key
     * @return  array|string    An array of values or single value.
     */
    public function getResource($key, $name = null);
}