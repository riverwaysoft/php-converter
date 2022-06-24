<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto\PhpType;

class PhpBaseType implements PhpTypeInterface
{
    private function __construct(
        private string $name,
    ) {
    }

    public static function int(): self
    {
        return new self('int');
    }

    public static function float(): self
    {
        return new self('float');
    }

    public static function string(): self
    {
        return new self('string');
    }

    public static function bool(): self
    {
        return new self('bool');
    }

    public static function mixed(): self
    {
        return new self('mixed');
    }

    public static function object(): self
    {
        return new self('object');
    }

    public static function array(): self
    {
        return new self('array');
    }

    public static function iterable(): self
    {
        return new self('iterable');
    }

    public static function null(): self
    {
        return new self('null');
    }

    public static function self(): self
    {
        return new self('self');
    }

    public function equalsTo(self $otherType): bool
    {
        return $this->name === $otherType->name;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
        ];
    }
}
