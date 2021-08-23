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
