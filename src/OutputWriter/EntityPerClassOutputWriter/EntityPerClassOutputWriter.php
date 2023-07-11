<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\EntityPerClassOutputWriter;

use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Language\ImportGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;

class EntityPerClassOutputWriter implements OutputWriterInterface
{
    /** @var OutputFile[] */
    private array $files = [];

    public function __construct(
        private FileNameGeneratorInterface $fileNameGenerator,
        private ImportGeneratorInterface $importGenerator,
    ) {
    }

    public function writeType(string $languageType, DtoType $dtoType): void
    {
        $relativeName = $this->fileNameGenerator->generateFileNameWithExtension($dtoType->getName());
        $fileContent = $this->importGenerator->generateFileContent($languageType, $dtoType);

        $this->files[] = new OutputFile(relativeName: $relativeName, content: $fileContent);
    }

    public function writeApiEndpoint(string $languageEndpoint, ApiEndpoint $apiEndpoint): void
    {
        throw new \Exception('Not implemented');
    }

    public function getTypes(): array
    {
        return $this->files;
    }

    public function reset(): void
    {
        $this->files = [];
    }
}
