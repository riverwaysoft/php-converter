<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\SingleFileOutputWriter;

use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;

class SingleFileOutputWriter implements OutputWriterInterface
{
    private OutputFile $outputFile;

    public function __construct(
        private string $relativeName,
    ) {
        $this->reset();
    }

    public function writeType(string $languageType, DtoType $dtoType): void
    {
        $content = sprintf("%s%s\n", $this->outputFile->isEmpty() ? '' : "\n", $languageType);
        $this->outputFile->appendContent($content);
    }

    public function writeApiEndpoint(string $languageEndpoint, ApiEndpoint $apiEndpoint): void
    {
        $this->outputFile->appendContent($languageEndpoint);
    }

    /** @return OutputFile[] */
    public function getTypes(): array
    {
        return [
            $this->outputFile,
        ];
    }

    public function reset(): void
    {
        $this->outputFile = new OutputFile($this->relativeName, '');
    }
}
