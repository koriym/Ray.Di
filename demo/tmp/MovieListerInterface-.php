<?php

namespace Ray\Di\Compiler;

$instance = new \MovieLister($prototype('FinderInterface-'));
$instance->setFinder01($prototype('FinderInterface-'), $prototype('FinderInterface-'), $prototype('FinderInterface-'));
$isSingleton = false;
return $instance;
