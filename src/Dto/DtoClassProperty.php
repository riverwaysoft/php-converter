<?php

declare(strict_types=1);

namespace App\Dto;

class DtoClassProperty
{
    public function __construct(
        public SingleType|UnionType $type,
        public string $name,
    ) {
    }
}
