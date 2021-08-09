<?php

declare(strict_types=1);

namespace App\Dto;

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

    public function merge(self $list)
    {
        foreach ($list->dtoList as $dto) {
            $this->dtoList[$dto->title] = $dto;
        }
    }
}
