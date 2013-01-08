<?php

namespace Gaufrette\Adapter;

/**
 * Interface which add supports for metadata
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
interface MetadataSupporter
{    
    /**
     * @param string $metaKey
     * 
     * @return boolean
     */
    public function isMetadataKeyAllowed($metaKey);    

}
