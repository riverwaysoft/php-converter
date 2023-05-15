<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto\ApiClient;

class ApiEndpointList
{
    /** @var array<string, ApiEndpoint> */
    private array $apiEndpointMap = [];


    /** @return ApiEndpoint[] */
    public function getList(): array
    {
        $values = array_values($this->apiEndpointMap);
        // Force stable alphabetical order of types
        usort(array: $values, callback: fn (ApiEndpoint $a, ApiEndpoint $b) => $a->url <=> $b->url);

        return $values;
    }

    public function add(ApiEndpoint $apiEndpoint): void
    {
        $apiEndpointHash = $apiEndpoint->url . $apiEndpoint->method->getType();
        if (!empty($this->apiEndpointMap[$apiEndpointHash])) {
            throw new \Exception(sprintf("Non-unique api endpoint with url %s and method %s", $apiEndpoint->url, $apiEndpoint->method->getType()));
        }

        $this->apiEndpointMap[$apiEndpointHash] = $apiEndpoint;
    }

    public function merge(self $list): void
    {
        foreach ($list->apiEndpointMap as $apiEndpoint) {
            $this->add($apiEndpoint);
        }
    }
}
