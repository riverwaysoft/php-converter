<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\EntityPerClassOutputWriter;

use Jawira\CaseConverter\Convert;
use Exception;

class SnakeCaseFileNameGenerator implements FileNameGeneratorInterface
{
    public function __construct(private string $extension)
    {
        if (!str_starts_with(haystack: $this->extension, needle: '.')) {
            throw new Exception(sprintf("Invalid file extension: %s\nA valid file extension should start with .", $this->extension));
        }
    }

    public function generateFileName(string $typeName): string
    {
        return (new Convert($typeName))->toSnake();
    }

    public function generateFileNameWithExtension(string $typeName): string
    {
        return $this->generateFileName($typeName) . $this->extension;
    }
}
