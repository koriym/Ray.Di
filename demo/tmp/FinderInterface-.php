<?php

namespace Ray\Di\Compiler;

$instance = new \DbFinder($prototype('DbInterface-'));
$instance->setDb($prototype('DbInterface-'));
$instance->setSorter($singleton('Sorter-'), $singleton('Sorter-'));
$isSingleton = false;
return $instance;
