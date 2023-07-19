<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto\ApiClient;

use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use JsonSerializable;

class ApiEndpointParam implements JsonSerializable
{
    public function __construct(
        public string $name,
        public PhpTypeInterface $type,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}
