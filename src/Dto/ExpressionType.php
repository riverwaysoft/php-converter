<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * What is expression: https://github.com/nikic/PHP-Parser/blob/master/doc/2_Usage_of_basic_components.markdown#node-tree-structure
 */
class ExpressionType implements \JsonSerializable
{
    public function __construct(private string $type)
    {
    }

    public static function class(): self
    {
        return new self('class');
    }

    public static function enum(): self
    {
        return new self('enum');
    }

    public function isEnum(): bool
    {
        return $this->type === 'enum';
    }

    public function isClass(): bool
    {
        return $this->type === 'class';
    }

    public function equals(ExpressionType $expressionType): bool
    {
        return $this->type === $expressionType->type;
    }

    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
        ];
    }
}
