<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto\PhpType;

class PhpUnknownType implements PhpTypeInterface
{
    // The constant is going to be used until this feature is implemented: https://github.com/riverwaysoft/php-converter/issues/36
    public const GENERIC_IGNORE_NO_RESOLVER = 'GENERIC_IGNORE_NO_RESOLVER';

    /**
     * @param array<string, mixed> $context
     * @param PhpTypeInterface[] $generics
     */
    public function __construct(
        private string $name,
        private array $generics = [],
        private array $context = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'context' => $this->context,
            'generics' => $this->generics,
        ];
    }

    /** @return PhpTypeInterface[] */
    public function getGenerics(): array
    {
        return $this->generics;
    }

    public function hasGenerics(): bool
    {
        return count($this->generics) > 0;
    }
}
