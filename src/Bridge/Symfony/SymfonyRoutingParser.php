<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Bridge\Symfony;

use function preg_match_all;

class SymfonyRoutingParser
{
    /** @return string[] */
    public static function parseRoute(string $route): array
    {
        $pattern = '/\{([^\/}]+)\}/';
        /** @var string[] $params */
        $params = [];

        preg_match_all($pattern, $route, $matches);
        foreach ($matches[1] as $param) {
            $params[] = $param;
        }

        return $params;
    }
}
