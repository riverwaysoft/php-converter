<?php

declare(strict_types=1);

namespace App\Dto;

class DtoEnumProperty
{
    public function __construct(
        public string $name,
        public string|int $value,
    ) {
    }
}
