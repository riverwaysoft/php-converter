<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor;

use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;

class AppendTextFileProcessor implements SingleOutputFileProcessorInterface
{
    public function __construct(private string $text)
    {
    }

    public function process(OutputFile $outputFile): OutputFile
    {
        return new OutputFile(
            relativeName: $outputFile->getRelativeName(),
            content: $outputFile->getContent() . $this->text,
        );
    }
}
