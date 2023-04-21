<?php

namespace Gaufrette\Adapter;

/**
 * Interface which add supports for metadata.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
interface MetadataSupporter
{
    public function setMetadata(string $key, array $content);

    public function getMetadata(string $key): array;
}
