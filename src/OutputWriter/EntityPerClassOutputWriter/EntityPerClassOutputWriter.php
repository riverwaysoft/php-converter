<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\EntityPerClassOutputWriter;

use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\OutputWriter\OutputFile;
use Riverwaysoft\DtoConverter\OutputWriter\OutputWriterInterface;

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

    public function getTypes(): array
    {
        return $this->files;
    }

    public function reset(): void
    {
        $this->files = [];
    }
}
