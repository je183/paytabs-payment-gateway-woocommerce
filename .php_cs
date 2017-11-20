<?php

$config = new Refinery29\CS\Config\Php56();
$config->getFinder()
    ->exclude('bootstrap')
    ->exclude('storage')
    ->exclude('vendor')
    ->name('*.php')
    ->notName('*.blade.php')
    ->notName('_ide_helper.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->notName('plugin.php')->in(__DIR__);

$cacheDir = getenv('TRAVIS') ? getenv('HOME') . '/.php-cs-fixer' : __DIR__;

$config->setCacheFile($cacheDir . '/.php_cs.cache');

return $config;