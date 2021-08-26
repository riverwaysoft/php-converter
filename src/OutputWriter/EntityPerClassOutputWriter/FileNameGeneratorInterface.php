<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter;

interface FileNameGeneratorInterface
{
    public function generateFileName(string $typeName): string;
    public function generateFileNameWithExtension(string $typeName): string;
}
