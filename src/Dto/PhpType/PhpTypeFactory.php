<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto\PhpType;

class PhpTypeFactory
{
    /** @param array<string, mixed> $context */
    public static function create(
        string $typeName,
        array $context = [],
    ): PhpBaseType|PhpUnknownType {
        return match ($typeName) {
            'int', => PhpBaseType::int(),
            'float' => PhpBaseType::float(),
            'string' => PhpBaseType::string(),
            'bool' => PhpBaseType::bool(),
            'mixed' => PhpBaseType::mixed(),
            'object' => PhpBaseType::object(),
            'array' => PhpBaseType::array(),
            'iterable', => PhpBaseType::iterable(),
            'null' => PhpBaseType::null(),
            'self' => PhpBaseType::self(),
            default => new PhpUnknownType($typeName, $context),
        };
    }
}
