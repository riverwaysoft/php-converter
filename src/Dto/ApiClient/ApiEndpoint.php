<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto\ApiClient;

use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;

class ApiEndpoint implements \JsonSerializable
{
    public function __construct(
        public string $url,
        public ApiEndpointMethod $method,
        public ?PhpTypeInterface $input,
        public ?PhpTypeInterface $output,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method->getType(),
            'input' => $this->input,
            'output' => $this->output,
        ];
    }
}
