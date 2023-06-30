<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor;

use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;

interface SingleOutputFileProcessorInterface
{
    public function process(OutputFile $outputFile): OutputFile;
}
