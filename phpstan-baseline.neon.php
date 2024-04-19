<?php declare(strict_types = 1);

// https://github.com/phpstan/phpstan/discussions/6604#discussioncomment-2133932
$includes = [];
if (PHP_VERSION_ID >= 80200) {
    $includes[] = __DIR__ . '/phpstan-baseline.neon';
}
$config = [];
$config['includes'] = $includes;

return $config;