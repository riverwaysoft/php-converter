<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter;

class SingleFileOutputWriter implements OutputWriterInterface
{
    private OutputFile $outputFile;

    public function __construct(private string $relativeName)
    {
        $this->reset();
    }

    public function writeType(string $languageType): void
    {
        $this->outputFile->appendContent($languageType . "\n\n");
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
