<?php

namespace Gaufrette\Util;

use FilterIterator;
use Iterator;

/**
 * FilterIterator for ListKeys in Local adapter
 *
 * @author  Massimiliano Arione <m.arione@bee-lab.net>
 */
class ListKeysFilterIterator  extends FilterIterator
{
    protected $filter, $length;

    /**
     * {@inheritDoc}
     */
    public function __construct(Iterator $iterator, $filter)
    {
        $this->filter = $filter;
        $this->length = strlen($filter);
        parent::__construct($iterator);
    }

    /**
     * {@inheritDoc}
     */
    public function accept()
    {
        return substr($this->current(), 0, $this->length) == $this->filter;
    }
}