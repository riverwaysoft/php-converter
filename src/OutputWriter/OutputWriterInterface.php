<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter;

use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\DtoConverter\Dto\DtoType;

interface OutputWriterInterface
{
    public function writeType(string $languageType, DtoType $dtoType): void;
    public function writeApiEndpoint(string $languageEndpoint, ApiEndpoint $apiEndpoint): void;
    /** @return OutputFile[] */
    public function getTypes(): array;
    public function reset(): void;
}
