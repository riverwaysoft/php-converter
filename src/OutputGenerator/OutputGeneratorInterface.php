<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator;

use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;

interface OutputGeneratorInterface
{
    /** @return OutputFile[] */
    public function generate(ConverterResult $converterResult): array;
}
