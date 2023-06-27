<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto\ApiClient;

use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;

class ApiEndpoint implements \JsonSerializable
{
    public function __construct(
        public string $route,
        public ApiEndpointMethod $method,
        public ?ApiEndpointParam $input,
        public ?PhpTypeInterface $output,
        /** @var ApiEndpointParam[] */
        public array $routeParams = [],
        /** @var ApiEndpointParam[] */
        public array $queryParams = [],
    ) {
        if (str_contains($this->route, '.')) {
            throw new \Exception(sprintf("Invalid character . in route %s", $this->route));
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'route' => $this->route,
            'routeParams' => $this->routeParams,
            'method' => $this->method->getType(),
            'input' => $this->input,
            'output' => $this->output,
            'queryParams' => $this->queryParams,
        ];
    }
}
