<?php

namespace Gaufrette\Adapter;

/**
 * Interface which add size calculation support to adapter.
 *
 * @author Markus Poerschke <markus@eluceo.de>
 */
interface SizeCalculator
{
    /**
     * @return false|int size of the specified key.
     */
    public function size(string $key): bool|int;
}
