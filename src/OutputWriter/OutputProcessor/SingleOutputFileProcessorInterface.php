<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputWriter\OutputProcessor;

use Riverwaysoft\DtoConverter\OutputWriter\OutputFile;

interface SingleOutputFileProcessorInterface
{
    public function process(OutputFile $outputFile): OutputFile;
}