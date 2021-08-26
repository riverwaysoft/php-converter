<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter;

use Riverwaysoft\DtoConverter\Dto\DtoType;

interface ImportGeneratorInterface
{
    public function generateFileContent(string $languageType, DtoType $dtoType): string;
}
