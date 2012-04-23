<?php

namespace Gaufrette\Util;

/**
 * Utility class for file sizes
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Size
{
    /**
     * Returns the size in bytes from the given content
     *
     * @param  string $content
     *
     * @return integer
     *
     * @todo handle the case the mbstring is not loaded
     */
    static public function fromContent($content)
    {
        return mb_strlen($content);
    }
}
