<?php

namespace Gaufrette\Adapter;

/**
 * Safe local adapter that encodes key to avoid the use of the directories
 * structure.
 *
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class SafeLocal extends Local
{
    public function computeKey(string $path): string
    {
        return base64_decode(parent::computeKey($path));
    }

    protected function computePath(string $key): string
    {
        return parent::computePath(base64_encode($key));
    }
}
