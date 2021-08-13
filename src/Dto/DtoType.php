<?php

declare(strict_types=1);

namespace App\Dto;

class DtoType
{
    public function __construct(
        public string $name,
        public ExpressionType $expressionType,
        /** @var DtoClassProperty[]|DtoEnumProperty[] */
        public array $properties,
    ) {
    }
}
