<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto\ApiClient;

class ApiEndpointList implements \JsonSerializable
{
    /** @var array<string, ApiEndpoint> */
    private array $apiEndpointMap = [];


    /** @return ApiEndpoint[] */
    public function getList(): array
    {
        $values = array_values($this->apiEndpointMap);
        // Force stable alphabetical order of types
        usort(array: $values, callback: fn (ApiEndpoint $a, ApiEndpoint $b) => $a->route <=> $b->route);

        return $values;
    }

    public function add(ApiEndpoint $apiEndpoint): void
    {
        $apiEndpointHash = $apiEndpoint->route . $apiEndpoint->method->getType();
        if (!empty($this->apiEndpointMap[$apiEndpointHash])) {
            throw new \Exception(sprintf(
                "Non-unique api endpoint with route %s and method %s",
                $apiEndpoint->route,
                $apiEndpoint->method->getType(),
            ));
        }

        $this->apiEndpointMap[$apiEndpointHash] = $apiEndpoint;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'map' => $this->getList(),
        ];
    }
}
