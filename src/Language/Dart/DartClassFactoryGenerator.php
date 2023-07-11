<?php

namespace Riverwaysoft\PhpConverter\Language\Dart;

use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\Language\UnknownTypeResolver\InlineTypeResolver;
use Webmozart\Assert\Assert;

class DartClassFactoryGenerator
{
    public function __construct(
        private string|null $excludePattern = null,
        private InlineTypeResolver|null $inlineTypeResolver = null,
    ) {
    }

    public function generateClassFactory(DtoType $dto, DtoList $dtoList): string
    {
        if (empty($dtoList->getList())) {
            return '';
        }

        if ($this->excludePattern && preg_match(pattern: $this->excludePattern, subject: $dto->getName())) {
            return '';
        }

        $factoryProperties = '';

        foreach ($dto->getProperties() as $property) {
            Assert::false($property instanceof DtoEnumProperty, "Dart factories only work in a class context, not enum");
            $propertyValue = $this->resolveFactoryProperty($property->getName(), $property->getType(), $dto, $dtoList);
            $factoryProperties .= sprintf("      %s: %s,\n", $property->getName(), $propertyValue);
        }

        return sprintf(
            "\n  factory %s.fromJson(Map<String, dynamic> json) {
    return %s(\n%s    );
  }\n",
            $dto->getName(),
            $dto->getName(),
            $factoryProperties,
        );
    }

    private function resolveFactoryProperty(
        string $propertyName,
        PhpTypeInterface $type,
        DtoType $dto,
        DtoList $dtoList,
        string $mapArgumentName = "json['%s']",
    ): string {
        if ($type instanceof PhpUnionType && $type->isNullable()) {
            return sprintf(
                "json['{$propertyName}'] != null ? %s : null",
                $this->resolveFactoryProperty($propertyName, $type->getFirstNotNullType(), $dto, $dtoList)
            );
        }

        if ($type instanceof PhpListType) {
            $collectionType = $type->getType();

            if ($collectionType instanceof PhpUnknownType) {
                $collectionInnerType = $collectionType->getName();
                return sprintf(
                    "List<%s>.from(json['%s'].map((e) => %s))",
                    $collectionInnerType,
                    $propertyName,
                    $this->resolveFactoryProperty($collectionInnerType, $collectionType, $dto, $dtoList, 'e'),
                );
            }

            if ($collectionType instanceof PhpBaseType) {
                $dartType = match (true) {
                    $collectionType->equalsTo(PhpBaseType::int()) => 'int',
                    $collectionType->equalsTo(PhpBaseType::float()) => 'num',
                    $collectionType->equalsTo(PhpBaseType::string()) => 'String',
                    $collectionType->equalsTo(PhpBaseType::bool()) => 'bool',
                    $collectionType->equalsTo(PhpBaseType::mixed()), $collectionType->equalsTo(PhpBaseType::iterable()), $collectionType->equalsTo(PhpBaseType::array()) => 'dynamic',
                    $collectionType->equalsTo(PhpBaseType::null()) => 'null',
                    $collectionType->equalsTo(PhpBaseType::self()) => $dto->getName(),
                    default => throw new \Exception(sprintf("Unknown base PHP type: %s", $type->jsonSerialize()))
                };

                return sprintf("List<%s>.from(json['%s'])", $dartType, $propertyName);
            }

            throw new \Exception(sprintf("Only PHP base types and class instance can be converted to collection. Property: %s#%s", $dto->getName(), $propertyName));
        }

        if ($type instanceof PhpUnknownType) {
            if ($type->getName() === 'DateTime' || $type->getName() === 'DateTimeImmutable') {
                return sprintf("DateTime.parse(json['%s'])", $propertyName);
            }

            $dtoType = $dtoList->getDtoByType($type->getName());
            if ($dtoType?->getExpressionType()->isAnyEnum()) {
                if ($dtoType->isStringEnum()) {
                    /** @noinspection PhpFormatFunctionParametersMismatchInspection */
                    return sprintf("%s.values.byName({$mapArgumentName})", $type->getName(), $propertyName);
                }
                /** @noinspection PhpFormatFunctionParametersMismatchInspection */
                return sprintf("%s.values[{$mapArgumentName}]", $type->getName(), $propertyName);
            }

            if ($this->inlineTypeResolver?->supports($type, $dto, $dtoList)) {
                $resolved = $this->inlineTypeResolver->resolve($type, $dto, $dtoList);
                if ($resolved instanceof PhpTypeInterface) {
                    return $this->resolveFactoryProperty($propertyName, $resolved, $dto, $dtoList, $mapArgumentName);
                }
            }
            /** @noinspection PhpFormatFunctionParametersMismatchInspection */
            return sprintf("%s.fromJson({$mapArgumentName})", $type->getName(), $propertyName);
        }

        return sprintf($mapArgumentName, $propertyName);
    }
}
