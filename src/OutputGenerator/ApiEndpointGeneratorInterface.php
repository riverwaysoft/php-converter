<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator;

use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\PhpConverter\Dto\DtoList;

interface ApiEndpointGeneratorInterface
{
    public function generate(ApiEndpoint $apiEndpoint, DtoList $dtoList): string;
}
