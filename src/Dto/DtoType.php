<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto;

class DtoType implements \JsonSerializable
{
    public function __construct(
        private string $name,
        private ExpressionType $expressionType,
        /** @var DtoClassProperty[]|DtoEnumProperty[] $properties */
        private array $properties,
    ) {
    }

    public function isNumericEnum(): bool
    {
        $numericEnums = array_filter(
            $this->properties,
            fn (DtoClassProperty|DtoEnumProperty $property) => $property instanceof DtoEnumProperty && $property->isNumeric()
        );

        return count($numericEnums) === count($this->properties);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExpressionType(): ExpressionType
    {
        return $this->expressionType;
    }

    /** @return DtoClassProperty[]|DtoEnumProperty[] */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'expressionType' => $this->expressionType,
            'properties' => $this->properties,
        ];
    }
}
