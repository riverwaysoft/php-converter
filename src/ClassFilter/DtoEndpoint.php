<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\ClassFilter;

#[\Attribute(\Attribute::TARGET_METHOD)]
class DtoEndpoint
{
    public function __construct(
        public string|null $returnMany = null,
        public string|null $returnOne = null,
    ) {
    }
}
