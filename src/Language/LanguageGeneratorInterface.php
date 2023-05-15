<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language;

use Riverwaysoft\DtoConverter\Ast\ConverterResult;
use Riverwaysoft\DtoConverter\OutputWriter\OutputFile;

interface LanguageGeneratorInterface
{
    /** @return OutputFile[] */
    public function generate(ConverterResult $converterResult): array;
}
