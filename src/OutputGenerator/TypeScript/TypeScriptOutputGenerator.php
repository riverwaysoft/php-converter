<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputGenerator\TypeScript;

use Exception;
use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\ExpressionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\OutputGenerator\ApiEndpointGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\OutputGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputGenerator\PropertyNameGeneratorInterface;
use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;
use Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor\OutputFilesProcessor;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;
use Webmozart\Assert\Assert;
use function array_map;
use function implode;
use function sprintf;

class TypeScriptOutputGenerator implements OutputGeneratorInterface
{
    private TypeScriptGeneratorOptions $options;

    private ApiEndpointGeneratorInterface $apiEndpointGenerator;

    /** @var PropertyNameGeneratorInterface[] */
    private array $propertyNameGenerators;

    /** @param PropertyNameGeneratorInterface[] $propertyNameGenerators */
    public function __construct(
        private OutputWriterInterface $outputWriter,
        private TypeScriptTypeResolver $typeResolver,
        private ?OutputFilesProcessor $outputFilesProcessor = null,
        array $propertyNameGenerators = [],
        ?TypeScriptGeneratorOptions $options = null,
    ) {
        $this->options = $options ?? new TypeScriptGeneratorOptions(useTypesInsteadOfEnums: false);
        $this->outputFilesProcessor = $this->outputFilesProcessor ?? new OutputFilesProcessor();
        $this->apiEndpointGenerator = new AxiosEndpointGenerator($this->typeResolver);
        $this->propertyNameGenerators = $propertyNameGenerators ?: [new TypeScriptPropertyNameGenerator()];
    }

    /** @return OutputFile[] */
    public function generate(ConverterResult $converterResult): array
    {
        $this->outputWriter->reset();

        $dtoList = $converterResult->dtoList;
        foreach ($dtoList->getList() as $dto) {
            $this->outputWriter->writeType($this->convertToTypeScriptType($dto, $dtoList), $dto);
        }

        $apiEndpointList = $converterResult->apiEndpointList;
        foreach ($apiEndpointList->getList() as $apiEndpoint) {
            $this->outputWriter->writeApiEndpoint($this->apiEndpointGenerator->generate($apiEndpoint, $dtoList), $apiEndpoint);
        }

        return $this->outputFilesProcessor->process($this->outputWriter->getTypes());
    }

    private function convertToTypeScriptType(DtoType $dto, DtoList $dtoList): string
    {
        if ($dto->getExpressionType()->equals(ExpressionType::class())) {
            $typeName = $dto->getName();
            if ($dto->isGeneric()) {
                $generics = array_map(fn (PhpUnknownType $generic) => $generic->getName(), $dto->getGenerics());
                $typeName .= sprintf("<%s>", join(', ', $generics));
            }
            return sprintf("export type %s = {%s\n};", $typeName, $this->convertToTypeScriptProperties($dto, $dtoList));
        }
        if ($dto->getExpressionType()->isAnyEnum()) {
            if ($this->shouldEnumBeConverterToUnion($dto)) {
                return sprintf("export type %s = %s;", $dto->getName(), $this->convertEnumToTypeScriptUnionProperties($dto->getProperties()));
            }
            return sprintf("export enum %s {%s\n}", $dto->getName(), $this->convertEnumToTypeScriptEnumProperties($dto->getProperties()));
        }
        throw new Exception('Unknown expression type ' . $dto->getExpressionType()->jsonSerialize());
    }

    // TS only supports string or number backed enums. If one of the enum values is null TS gives compilation error
    // In this case it's better to convert enum to union type
    private function shouldEnumBeConverterToUnion(DtoType $dto): bool
    {
        Assert::true($dto->getExpressionType()->isAnyEnum());

        if ($this->options->useTypesInsteadOfEnums) {
            return true;
        }

        foreach ($dto->getProperties() as $property) {
            if ($property->isNull()) {
                return true;
            }
        }

        return false;
    }

    private function getPropertyNameGenerator(DtoType $dto): PropertyNameGeneratorInterface
    {
        if (count($this->propertyNameGenerators) === 1) {
            return $this->propertyNameGenerators[0];
        }

        foreach ($this->propertyNameGenerators as $propertyNameGenerator) {
            if ($propertyNameGenerator->supports($dto)) {
                return $propertyNameGenerator;
            }
        }

        throw new Exception('Property name generator not found for type ' . $dto->getName());
    }

    private function convertToTypeScriptProperties(DtoType $dto, DtoList $dtoList): string
    {
        $string = '';

        /** @param DtoClassProperty[] $properties */
        $properties = $dto->getProperties();
        $propertyNameGenerator = $this->getPropertyNameGenerator($dto);

        foreach ($properties as $property) {
            $string .= sprintf(
                "\n  %s: %s;",
                $propertyNameGenerator->generate($property),
                $this->typeResolver->getTypeFromPhp($property->getType(), $dto, $dtoList)
            );
        }

        return $string;
    }

    /** @param DtoEnumProperty[] $properties */
    private function convertEnumToTypeScriptEnumProperties(array $properties): string
    {
        $string = '';

        foreach ($properties as $property) {
            $propertyValue = $property->isNumeric()
                ? $property->getValue()
                : sprintf("'%s'", $property->getValue());

            $string .= sprintf("\n  %s = %s,", $property->getName(), $propertyValue);
        }

        return $string;
    }

    /** @param DtoEnumProperty[] $properties */
    private function convertEnumToTypeScriptUnionProperties(array $properties): string
    {
        $propertyValues = array_map(function (DtoEnumProperty $property) {
            if ($property->isNumeric()) {
                return $property->getValue();
            }
            if ($property->isNull()) {
                return 'null';
            }
            return sprintf("'%s'", $property->getValue());
        }, $properties);

        return implode(' | ', $propertyValues);
    }
}
