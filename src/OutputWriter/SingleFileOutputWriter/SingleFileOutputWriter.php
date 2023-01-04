<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\SingleFileOutputWriter;

use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\OutputWriter\OutputFile;
use Riverwaysoft\DtoConverter\OutputWriter\OutputWriterInterface;

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
