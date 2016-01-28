<?php

namespace Jade\Filter;

use Jade\Compiler;
use Jade\Nodes\Filter;

class Cdata Extends AbstractFilter
{
    public function __invoke(Filter $node, Compiler $compiler)
    {
        return "<![CDATA[\n" . $this->getNodeString($node, $compiler) . "\n]]>";
    }
}
