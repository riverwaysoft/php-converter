<?php

namespace Riverwaysoft\DtoConverter\Language\Dart;

use Riverwaysoft\DtoConverter\Dto\DtoEnumProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;
use Webmozart\Assert\Assert;

class DartClassFactoryGenerator
{
    public function __construct(private string|null $includePattern = null)
    {

    }

    public function generateClassFactory(DtoType $dto, DtoList $dtoList): string
    {
        if (empty($dtoList->getList())) {
            return '';
        }

        if ($this->includePattern && !preg_match(pattern: $this->includePattern, subject: $dto->getName())) {
           return '';
        }

        $factoryProperties = '';

        foreach ($dto->getProperties() as $property) {
            Assert::false($property instanceof DtoEnumProperty, "Dart factories only work in a class context, not enum");
            $propertyValue = $this->resolveFactoryProperty($property->getName(), $property->getType(), $dtoList);
            $factoryProperties .= sprintf("      %s: %s,\n", $property->getName(), $propertyValue);
        }

        return sprintf("\n  factory %s.fromJson(Map<String, dynamic> json) {
    return %s(\n%s    );
  }\n",
            $dto->getName(),
            $dto->getName(),
            $factoryProperties,
        );
    }

    private function resolveFactoryProperty(string $propertyName, PhpTypeInterface $type, DtoList $dtoList): string
    {
        if ($type instanceof PhpUnionType && $type->isNullable()) {
            return sprintf("json['{$propertyName}'] != null ? %s : null", $this->resolveFactoryProperty($propertyName, $type->getFirstNotNullType(), $dtoList));
        }

        if ($type instanceof PhpListType) {
            $collectionType = $type->getType();
            if (!($collectionType instanceof PhpUnknownType)) {
                throw new \Exception('Only class instance can be converted to collection');
            }
            $class = $collectionType->getName();
            return sprintf("List<%s>.from(json['%s'].map((e) => %s.fromJson(e)))", $class, $propertyName, $class);
        }

        if ($type instanceof PhpUnknownType) {
            if ($type->getName() === 'DateTime' || $type->getName() === 'DateTimeImmutable') {
                return sprintf("DateTime.parse(json['%s'])", $propertyName);
            }

            if ($dtoList->getDtoByType($type->getName())?->getExpressionType()->isAnyEnum()) {
                return sprintf("%s.values[json['%s']]", $type->getName(), $propertyName);
            }

            return sprintf("%s.fromJson(json['%s'])", $type->getName(), $propertyName);
        }

        return sprintf("json['%s']", $propertyName);
    }
}