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
        foreach ($this->dtoList as $dto) {
            if ($dto->getName() === $type) {
                return true;
            }
        }
        return false;
    }
}
