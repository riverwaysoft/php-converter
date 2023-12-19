<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\Go;

use Exception;
use LogicException;
use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\OutputGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\UnsupportedTypeException;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;
use Webmozart\Assert\Assert;

final class GoOutputGenerator implements OutputGeneratorInterface
{
    public function __construct(
        private OutputWriterInterface $outputWriter,
//        /** @var UnknownTypeResolverInterface[] $unknownTypeResolvers */
//        private array $unknownTypeResolvers = [],
    )
    {
    }

    /** @throws UnsupportedTypeException */
    public function generate(ConverterResult $converterResult): array
    {
        $this->outputWriter->reset();

        $dtoList = $converterResult->dtoList;
        foreach ($dtoList->getList() as $dto) {
            $this->outputWriter->writeType(
                $this->convertToGoType($dto),
                $dto
            );
        }

        return $this->outputWriter->getTypes();
    }

    /** @throws UnsupportedTypeException */
    private function convertToGoType(DtoType $dto): string
    {
        if ($dto->getExpressionType()->isAnyEnum()) {
            return $this->convertAsEnum($dto);
        } else {
            return $this->convert($dto);
        }
    }

    /** @var array<string, bool> $enumUsedProps */
    private static array $enumUsedProps;

    private static function getEnumPropName(
        DtoEnumProperty $prop,
        DtoType         $dto,
    ): string
    {
        $name = $prop->getName();

        if (isset(self::$enumUsedProps[$name])) {
            return $name . $dto->getName();
        }

        self::$enumUsedProps[$name] = true;

        return $name;
    }

    private function convertAsEnum(DtoType $dto): string
    {
        $propsString = '';

        $props = $dto->getProperties();

        foreach ($props as $prop) {
            /** @var DtoEnumProperty $prop */
            $propsString .= sprintf(
                "\n  %s %s = \"%s\";",
                self::getEnumPropName($prop, $dto),
                $dto->getName(),
                $prop->getValue()
            );
        }

        if (!isset($props[0])) {
            throw new LogicException('Can\'t check enum type');
        }

        /** @var DtoEnumProperty $firstProp */
        $firstProp = $props[0];
        if (is_int($firstProp->getValue())) {
            $t = 'int';
        } elseif (is_string($firstProp->getValue())) {
            $t = 'string';
        } else {
            throw new LogicException('Unknown enum prop type');
        }

        return sprintf(
            "type %s %s \n\nconst (%s\n)",
            $dto->getName(),
            $t,
            $propsString
        );
    }

    /** @throws UnsupportedTypeException */
    private function convert(DtoType $dto): string
    {
        return sprintf(
            "type %s struct {%s\n}",
            $dto->getName(),
            $this->convertToGoProperties($dto)
        );
    }

    /** @throws UnsupportedTypeException */
    private function convertToGoProperties(
        DtoType $dto,
    ): string
    {
        $string = '';

        $props = $dto->getProperties();

        foreach ($props as $prop) {
            /** @var DtoClassProperty $prop */
            $type = $this->getGoTypeFromPhp($prop->getType());
            $string .= sprintf(
                "\n  %s %s",
                $prop->getName(),
                $type,
            );
        }

        return $string;
    }

    private function checkValidUnion(PhpUnionType $type): void
    {
        Assert::greaterThan(
            $type->getTypes(),
            2,
            "Go does not support union types"
        );
        Assert::true(
            $type->isNullable(),
            "Go only supports nullable union types"
        );
    }

    /** @var array<string, string> */
    private const BASE_TYPES_MAPPING = [
        'float' => 'float64',
        'int' => 'int',
        'string' => 'string',
        'bool' => 'bool',
        'mixed' => 'any',
        'object' => 'interface{}',
    ];

    private function getBaseType(PhpBaseType $type): string
    {
        /** @var array{name: string} $serialized */
        $serialized = $type->jsonSerialize();
        $typeName = $serialized['name'];

        foreach (self::BASE_TYPES_MAPPING as $phpType => $goType) {
            if ($typeName === $phpType) {
                return $goType;
            }
        }
        throw new LogicException("Unknown base PHP type $typeName");
    }

    /**
     * @throws UnsupportedTypeException
     * @throws Exception
     */
    private function getGoTypeFromPhp(
        PhpTypeInterface $type,
    ): string
    {
        switch ($type::class) {
            case PhpOptionalType::class:
                return $this->getGoTypeFromPhp($type->getType());
            case PhpUnionType::class:
                $this->checkValidUnion($type);
                $notNullType = $type->getFirstNotNullType();
                $typeStr = $this->getGoTypeFromPhp($notNullType);

                return sprintf('*%s', $typeStr);
            case PhpListType::class:
                $typeStr = $this->getGoTypeFromPhp($type->getType());

                return sprintf('[]%s', $typeStr);
            case PhpBaseType::class:
                return $this->getBaseType($type);
            case PhpUnknownType::class:
                return $this->handleUnknownType($type);
            default:
                throw new LogicException('Undefined type');
        }
    }

    private function handleUnknownType(PhpUnknownType $type): string
    {
//        foreach ($this->unknownTypeResolvers as $resolver) {
//            if ($resolver->supports($type, null, new DtoList())) {
//                return $resolver->resolve($type, null, new DtoList());
//            }
//        }
        throw new LogicException('Not supported yet');
    }
}