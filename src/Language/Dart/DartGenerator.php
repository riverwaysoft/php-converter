<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\Dart;

use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoEnumProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;
use Riverwaysoft\DtoConverter\Language\LanguageGeneratorInterface;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolverInterface;
use Webmozart\Assert\Assert;

class DartGenerator implements LanguageGeneratorInterface
{
    public function __construct(
        /** @var UnknownTypeResolverInterface[] */
        private array $unknownTypeResolvers = [],
    ) {
    }

    public function generate(DtoList $dtoList): string
    {
        $string = '';

        foreach ($dtoList->getList() as $dto) {
            $string .= $this->convertToDartType($dto, $dtoList) . "\n\n";
        }

        return $string;
    }

    private function convertToDartType(DtoType $dto, DtoList $dtoList): string
    {
        if ($dto->getExpressionType()->equals(ExpressionType::class())) {
            return sprintf(
                "class %s {%s\n\n  %s({%s\n  })\n}",
                $dto->getName(),
                $this->convertToDartProperties($dto->getProperties(), $dtoList),
                $dto->getName(),
                $this->generateConstructor($dto->getProperties()),
            );
        }

        if ($dto->getExpressionType()->equals(ExpressionType::enum())) {
            Assert::true(
                $dto->isNumericEnum(),
                sprintf("Dart only supports only numeric enum. Enum %s is not supported", $dto->getName())
            );

            return sprintf("enum %s {%s\n}", $dto->getName(), $this->convertEnumToTypeScriptProperties($dto->getProperties()));
        }

        throw new \Exception('Unknown expression type '.$dto->getExpressionType()->jsonSerialize());
    }

    /** @param DtoClassProperty[] $properties */
    private function convertToDartProperties(array $properties, DtoList $dtoList)
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n  final %s %s;", $this->getDartTypeFromPhp($property->getType(), $dtoList), $property->getName());
        }

        return $string;
    }

    /** @param DtoClassProperty[] $properties */
    private function generateConstructor(array $properties): string
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n    required this.%s,", $property->getName());
        }

        return $string;
    }

    private function getDartTypeFromPhp(UnionType|SingleType $type, DtoList $dtoList)
    {
        if ($type instanceof UnionType) {
            Assert::greaterThan($type->getTypes(), 2, "Dart does not support union types");
            Assert::true($type->isNullable());
            /** @var SingleType|null $notNullType */
            $notNullType = null;
            foreach ($type->getTypes() as $type) {
                if (!$type->isNull()) {
                    $notNullType = $type;
                }
            }
            Assert::notNull($notNullType);
            return sprintf('%s?', $this->getDartTypeFromPhp($notNullType, $dtoList));
        }

        if ($type->isList()) {
            return sprintf('List<%s>', $this->getDartTypeFromPhp(new SingleType($type->getName()), $dtoList));
        }

        return match ($type->getName()) {
            'int' => 'int',
            'float' => 'double',
            'string' => 'String',
            'bool' => 'bool',
            'mixed', 'object', 'array' => 'Object',
            'null' => 'null',
            default => $this->handleUnknownType($type, $dtoList),
        };
    }

    private function handleUnknownType(SingleType $type, DtoList $dtoList): string
    {
        if ($dtoList->hasDtoWithType($type->getName())) {
            return $type->getName();
        }

        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type)) {
                return $unknownTypeResolver->resolve($type, $dtoList);
            }
        }

        throw new \InvalidArgumentException(sprintf("PHP Type %s is not supported", $type->getName()));
    }

    /** @param DtoEnumProperty[] $properties */
    private function convertEnumToTypeScriptProperties(array $properties)
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n  %s,", $property->getName());
        }

        return $string;
    }
}
