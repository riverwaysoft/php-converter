<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Language;

use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;

interface LanguageGeneratorInterface
{
    /** @return OutputFile[] */
    public function generate(ConverterResult $converterResult): array;
}
