<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\TypeScript;

class TypeScriptGeneratorOptions
{
    public function __construct(
        public bool $useTypesInsteadOfEnums,
    ) {
    }
}
