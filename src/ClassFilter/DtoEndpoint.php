<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\ClassFilter;

#[\Attribute(\Attribute::TARGET_METHOD)]
class DtoEndpoint
{
    public function __construct(
        public string|null $returnMany,
        public string|null $returnOne,
    ) {
        // TODO: checks if this code gets executed
        if ($this->returnMany && $this->returnOne) {
            throw new \Exception('DtoEndpoint should either have $returnMany or $returnOne');
        }
        if (!$this->returnMany && !$this->returnOne) {
            throw new \Exception('DtoEndpoint requires $returnMany or $returnOne to be not empty');
        }
    }
}
