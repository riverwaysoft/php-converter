<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Ast;

use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointList;
use Riverwaysoft\DtoConverter\Dto\DtoList;

class ConverterResult
{
    public function __construct(
        public DtoList $dtoList,
        public ApiEndpointList|null $apiEndpointList = null
    ) {
    }
}
