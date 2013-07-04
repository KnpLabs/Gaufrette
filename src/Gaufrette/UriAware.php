<?php

namespace Gaufrette;

/**
 * Interface for files that know their URI in internet
 * Useful e.g. for certain types of CDN that return the file uri immediately when new file is added
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
interface UriAware
{
    /**
     * @return string uri
     */
    public function getUri();

    /**
     * @param string $uri
     */
    public function setUri($uri);

}
