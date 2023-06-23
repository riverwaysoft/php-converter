<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto\ApiClient;

use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;

class ApiEndpointParam implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public PhpBaseType $type,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'type' => $this->type
        ];
    }
}
