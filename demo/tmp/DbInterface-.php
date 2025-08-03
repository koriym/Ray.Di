<?php

namespace Ray\Di\Compiler;

$instance = new \Db('msql:host=localhost;dbname=test', 'root', '');
$isSingleton = false;
return $instance;
