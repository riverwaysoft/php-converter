<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto;

/**
 * An expression is either class or enum: https://github.com/nikic/PHP-Parser/blob/master/doc/2_Usage_of_basic_components.markdown#node-tree-structure
 */
class ExpressionType implements \JsonSerializable
{
    private function __construct(private string $type)
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

    public function equals(ExpressionType $expressionType): bool
    {
        return $this->type === $expressionType->type;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'type' => $this->type,
        ];
    }
}
