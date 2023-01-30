<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Dto;

class DtoList
{
    /** @var array<string, DtoType> */
    private array $dtoList = [];

    public function addDto(DtoType $dto): void
    {
        if (!empty($this->dtoList[$dto->getName()])) {
            throw new \Exception(sprintf("Non-unique class name %s ", $dto->getName()));
        }
        $this->dtoList[$dto->getName()] = $dto;
    }

    /** @return DtoType[] */
    public function getList(): array
    {
        $values = array_values($this->dtoList);
        // Force stable alphabetical order of types
        usort(array: $values, callback: fn ($a, $b) => $a <=> $b);

        return $values;
    }

    public function merge(self $list): void
    {
        foreach ($list->dtoList as $dto) {
            $this->addDto($dto);
        }
    }

    public function hasDtoWithType(string $type): bool
    {
        return $this->getDtoByType($type) !== null;
    }

    public function getDtoByType(string $type): DtoType|null
    {
        foreach ($this->dtoList as $dto) {
            if ($dto->getName() === $type) {
                return $dto;
            }
        }
        return null;
    }
}
