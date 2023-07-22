<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpointList;
use Riverwaysoft\PhpConverter\Dto\DtoList;

class ConverterResult
{
    public DtoList $dtoList;

    public ApiEndpointList $apiEndpointList;

    public function __construct()
    {
        $this->dtoList = new DtoList();
        $this->apiEndpointList = new ApiEndpointList();
    }

    public function merge(self $result): void
    {
        foreach ($result->dtoList->getList() as $anotherDto) {
            $this->dtoList->add($anotherDto);
        }
        foreach ($result->apiEndpointList->getList() as $anotherEndpoint) {
            $this->apiEndpointList->add($anotherEndpoint);
        }
    }
}
