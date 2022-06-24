<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto\PhpType;

class PhpTypeFactory
{
    public function create(string $typeName): PhpBaseType|PhpUnknownType
    {
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
            default => new PhpUnknownType($typeName),
        };
    }
}
