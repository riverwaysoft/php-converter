<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\OutputProcessor;

use Riverwaysoft\DtoConverter\OutputWriter\OutputFile;

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
