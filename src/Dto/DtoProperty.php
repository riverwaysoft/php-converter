<?php

declare(strict_types=1);

namespace App\Dto;

class DtoProperty
{
    public function __construct(
        public SingleType|UnionType $type,
        public string $name,
    ) {
    }
}
