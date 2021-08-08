<?php

declare(strict_types=1);

namespace App\Dto;

class UnionType
{
    public function __construct(
        /** @var SingleType[] $types */
        public array $types,
    )
    {

    }
}