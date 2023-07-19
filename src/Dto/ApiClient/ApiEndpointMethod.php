<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto\ApiClient;

use Exception;

class ApiEndpointMethod
{
    private function __construct(
        private string $type,
    ) {
    }

    public static function get(): self
    {
        return new self('get');
    }

    public static function post(): self
    {
        return new self('post');
    }

    public static function put(): self
    {
        return new self('put');
    }

    public static function patch(): self
    {
        return new self('patch');
    }

    public static function delete(): self
    {
        return new self('delete');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function equals(self $apiEndpointMethod): bool
    {
        return $this->type === $apiEndpointMethod->type;
    }

    public static function fromString(string $methodString): self
    {
        $methodLowerCased = mb_strtolower($methodString);

        return match ($methodLowerCased) {
            'get' => self::get(),
            'put' => self::put(),
            'patch' => self::patch(),
            'post' => self::post(),
            'delete' => self::delete(),
            default => throw new Exception('Unsupported method: ' . $methodString),
        };
    }
}
