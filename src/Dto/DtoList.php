<?php

declare(strict_types=1);

namespace App\Dto;

class DtoList
{
    /** @var array<string, DtoType> */
    private array $dtoList = [];

    public function addDto(DtoType $dto): void
    {
        if (!empty($this->dtoList[$dto->name])) {
            throw new \Exception(sprintf("Non-unique class name %s ", $dto->name));
        }
        $this->dtoList[$dto->name] = $dto;
    }

    /** @return DtoType[] */
    public function getList(): array
    {
        $values = array_values($this->dtoList);
        usort(array: $values, callback: fn ($a, $b) => $a <=> $b);

        return $values;
    }

    public function merge(self $list)
    {
        foreach ($list->dtoList as $dto) {
            $this->addDto($dto);
        }
    }

    public function hasDtoWithType(string $type): bool
    {
        foreach ($this->dtoList as $dto) {
            if ($dto->name === $type) {
                return true;
            }
        }
        return false;
    }
}
