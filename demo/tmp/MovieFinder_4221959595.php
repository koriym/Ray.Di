<?php

declare(strict_types=1);

use Ray\Di\Di\Assisted;
use Ray\Di\Di\Inject;

class MovieFinder_4221959595 extends MovieFinder implements \Ray\Aop\WeavedInterface 
{
    use \Ray\Aop\InterceptTrait;
    public function find($name, #[\Ray\Di\Di\Inject()] null|\FinderInterface $finder = NULL)
    {
        return $this->_intercept(__FUNCTION__, func_get_args());
    }
}