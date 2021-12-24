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
        foreach ($this->properties as $property) {
            if (!($property instanceof DtoEnumProperty && $property->isNumeric())) {
                return false;
            }
        }

        return true;
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

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'expressionType' => $this->expressionType,
            'properties' => $this->properties,
        ];
    }
}
