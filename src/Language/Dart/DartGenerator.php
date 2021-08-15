<?php

declare(strict_types=1);

namespace App\Language\Dart;

use App\Dto\DtoClassProperty;
use App\Dto\DtoEnumProperty;
use App\Dto\DtoList;
use App\Dto\DtoType;
use App\Dto\ExpressionType;
use App\Dto\SingleType;
use App\Dto\UnionType;
use App\Language\LanguageGeneratorInterface;
use App\Language\UnknownTypeResolverInterface;
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
        if ($dto->expressionType->equals(ExpressionType::class())) {
            return sprintf(
                "class %s {%s\n\n  %s({%s\n  })\n}",
                $dto->name,
                $this->convertToDartProperties($dto->properties, $dtoList),
                $dto->name,
                $this->generateConstructor($dto->properties),
            );
        }

        if ($dto->expressionType->equals(ExpressionType::enum())) {
            Assert::true(
                $dto->isNumericEnum(),
                sprintf("Dart only supports only numeric enum. Enum %s is not supported", $dto->name)
            );

            return sprintf("enum %s {%s\n}", $dto->name, $this->convertEnumToTypeScriptProperties($dto->properties));
        }

        throw new \Exception('Unknown expression type '.$dto->expressionType->jsonSerialize());
    }

    /** @param DtoClassProperty[] $properties */
    private function convertToDartProperties(array $properties, DtoList $dtoList)
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n  final %s %s;", $this->getDartTypeFromPhp($property->type, $dtoList), $property->name);
        }

        return $string;
    }

    /** @param DtoClassProperty[] $properties */
    private function generateConstructor(array $properties): string
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n    required this.%s,", $property->name);
        }

        return $string;
    }

    private function getDartTypeFromPhp(UnionType|SingleType $type, DtoList $dtoList)
    {
        if ($type instanceof UnionType) {
            Assert::greaterThan($type->types, 2, "Dart does not support union types");
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

        if ($type->isList) {
            return sprintf('List<%s>', $this->getDartTypeFromPhp(new SingleType($type->name), $dtoList));
        }

        return match ($type->name) {
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
        if ($dtoList->hasDtoWithType($type->name)) {
            return $type->name;
        }

        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type)) {
                return $unknownTypeResolver->resolve($type);
            }
        }

        throw new \InvalidArgumentException(sprintf("PHP Type %s is not supported", $type->name));
    }

    /** @param DtoEnumProperty[] $properties */
    private function convertEnumToTypeScriptProperties(array $properties)
    {
        $string = '';

        foreach ($properties as $property) {
            $string .= sprintf("\n  %s,", $property->name);
        }

        return $string;
    }
}
