<?php

declare(strict_types=1);

putenv('TMPDIR=' . __DIR__ . '/tmp');

require_once dirname(__DIR__) . '/vendor/autoload.php';

$deleteFiles = static function (string $path) use (&$deleteFiles): void {
    foreach (array_filter((array) glob($path . '/*')) as $file) {
        is_dir($file) ? $deleteFiles($file) : unlink($file);
        @rmdir($file);
    }
};

$deleteFiles(__DIR__ . '/tmp');
$deleteFiles(__DIR__ . '/compiler/tmp');
