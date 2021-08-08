<?php

declare(strict_types=1);

namespace App;

class DtoList
{
    private array $dtoList = [];

    public function addDto(DtoType $dto): void
    {
        if (!empty($this->dtoList[$dto->title])) {
            throw new \Exception(sprintf("Non-unique class name %s ", $dto->title));
        }
        $this->dtoList[$dto->title] = $dto;
    }

    /** @return DtoType[] */
    public function getList(): array
    {
        return array_values($this->dtoList);
    }
}