<?php

declare(strict_types=1);

namespace App\Dto;

class DtoType
{
    public function __construct(
        public string $title,
        /** @var DtoProperty[] */
        public array $properties,
    ) {
    }
}
