<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Language;

use Riverwaysoft\PhpConverter\Dto\DtoType;

interface ImportGeneratorInterface
{
    public function generateFileContent(string $languageType, DtoType $dtoType): string;
}
