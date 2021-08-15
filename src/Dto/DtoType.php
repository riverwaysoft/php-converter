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

    public function isNumericEnum(): bool
    {
        $numericEnums = array_filter(
            $this->properties,
            fn (DtoClassProperty|DtoEnumProperty $property) => $property instanceof DtoEnumProperty && is_numeric($property->value)
        );

        return count($numericEnums) === count($this->properties);
    }
}
