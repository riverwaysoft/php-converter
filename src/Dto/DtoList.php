<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Dto;

class DtoList
{
    /** @var array<string, DtoType> */
    private array $dtoMap = [];

    public function add(DtoType $dto): void
    {
        if (!empty($this->dtoMap[$dto->getName()])) {
            throw new \Exception(sprintf("Non-unique class name %s", $dto->getName()));
        }
        $this->dtoMap[$dto->getName()] = $dto;
    }

    /** @return DtoType[] */
    public function getList(): array
    {
        $values = array_values($this->dtoMap);
        // Force stable alphabetical order of types
        usort(array: $values, callback: fn (DtoType $a, DtoType $b) => $a->getName() <=> $b->getName());

        return $values;
    }

    public function hasDtoWithType(string $type): bool
    {
        return $this->getDtoByType($type) !== null;
    }

    public function getDtoByType(string $type): DtoType|null
    {
        foreach ($this->dtoMap as $dto) {
            if ($dto->getName() === $type) {
                return $dto;
            }
        }
        return null;
    }
}
