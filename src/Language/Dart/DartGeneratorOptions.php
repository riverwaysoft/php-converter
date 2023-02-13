<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\Dart;

class DartGeneratorOptions
{
    public function __construct(
        public bool $addEquitable,
        public bool $addFactory,
    ) {
    }
}
