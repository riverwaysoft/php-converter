<?php

namespace Riverwaysoft\DtoConverter\Language\Dart;

use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoType;

class DartEquitableGenerator
{
    public function __construct(private string|null $includePattern = null)
    {

    }

    public function generateEquitableHeader(DtoType $dto): string
    {
        if ($this->doesntMatch($dto)) {
            return '';
        }

        return ' extends Equatable';
    }

    private function doesntMatch(DtoType $dto): bool
    {
        return $this->includePattern && !preg_match(pattern: $this->includePattern, subject: $dto->getName());
    }

    public function generateEquitableId(DtoType $dto): string
    {
        if ($this->doesntMatch($dto)) {
            return '';
        }

        $properties = $dto->getProperties();
        $propertiesNames = array_map(fn(DtoClassProperty $property) => $property->getName(), $properties);
        $propertiesString = implode(separator: ', ', array: $propertiesNames);

        return sprintf("\n  @override
  List<dynamic> get props => [%s];\n", $propertiesString);
    }
}