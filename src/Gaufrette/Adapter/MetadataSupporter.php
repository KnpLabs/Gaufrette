<?php

namespace Gaufrette\Adapter;

/**
 * Interface which add supports for metadata.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 *
 * @TODO: Re-determine the behavior that should be implemeted for this interface - i.e. AwsS3 & AzureBlobStorage behavior differs
 */
interface MetadataSupporter
{
    /**
     * @param string $key
     * @param array  $content
     */
    public function setMetadata($key, $content);

    /**
     * @param string $key
     *
     * @return array
     */
    public function getMetadata($key);
}
