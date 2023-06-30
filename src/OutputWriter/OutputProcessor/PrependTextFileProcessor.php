<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor;

use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;

class PrependTextFileProcessor implements SingleOutputFileProcessorInterface
{
    public function __construct(private string $text)
    {
    }

    public function process(OutputFile $outputFile): OutputFile
    {
        return new OutputFile(
            relativeName: $outputFile->getRelativeName(),
            content: $this->text . $outputFile->getContent(),
        );
    }
}
