<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Language\TypeScript;

use Jawira\CaseConverter\Convert;
use Riverwaysoft\DtoConverter\Ast\ConverterResult;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoEnumProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\DtoConverter\Language\LanguageGeneratorInterface;
use Riverwaysoft\DtoConverter\Language\UnknownTypeResolver\UnknownTypeResolverInterface;
use Riverwaysoft\DtoConverter\Language\UnsupportedTypeException;
use Riverwaysoft\DtoConverter\OutputWriter\OutputFile;
use Riverwaysoft\DtoConverter\OutputWriter\OutputProcessor\OutputFilesProcessor;
use Riverwaysoft\DtoConverter\OutputWriter\OutputWriterInterface;
use Webmozart\Assert\Assert;

class TypeScriptGenerator implements LanguageGeneratorInterface
{
    private TypeScriptGeneratorOptions $options;

    public function __construct(
        private OutputWriterInterface $outputWriter,
        /** @var UnknownTypeResolverInterface[] $unknownTypeResolvers */
        private array $unknownTypeResolvers = [],
        private ?OutputFilesProcessor $outputFilesProcessor = null,
        ?TypeScriptGeneratorOptions $options = null,
    ) {
        $this->options = $options ?? new TypeScriptGeneratorOptions(useTypesInsteadOfEnums: false, apiClient: false);
        $this->outputFilesProcessor = $this->outputFilesProcessor ?? new OutputFilesProcessor();
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
            $this->outputWriter->writeApiEndpoint($this->convertToTypeScriptEndpoint($apiEndpoint, $dtoList), $apiEndpoint);
        }

        return $this->outputFilesProcessor->process($this->outputWriter->getTypes());
    }

    private function convertToTypeScriptEndpoint(ApiEndpoint $apiEndpoint, DtoList $dtoList): string
    {
        $string = "export const %s = (%s): %s => {\n%s\n}\n\n";

        $nameOriginal = str_replace('/', '_', $apiEndpoint->route) . '_' . $apiEndpoint->method->getType();
        $nameOriginal = str_replace(['{', '}'], '', $nameOriginal);

        $name = (new Convert($nameOriginal))->toCamel();

        $params = implode(', ', array_map(
            fn (string $param): string => "{$param}: string",
            $apiEndpoint->routeParams,
        ));

        $inputType = null;
        if ($apiEndpoint->input) {
            $inputType = $this->getTypeScriptTypeFromPhp($apiEndpoint->input, null, $dtoList);
            $params = implode(', ', array_filter([$params, "body: ${inputType}"]));
        }

        $form = $inputType !== null ? sprintf(', body') : '';

        $outputType = $apiEndpoint->output ? $this->getTypeScriptTypeFromPhp($apiEndpoint->output, null, $dtoList) : 'null';

        $returnType = sprintf('Promise<%s>', $outputType);

        $route = $this->injectJavaScriptInterpolatedVariables($apiEndpoint->route);
        $body = sprintf('  return axios
    .%s<%s>(`%s`%s)
    .then(response => response.data);', $apiEndpoint->method->getType(), $outputType, $route, $form);

        return sprintf($string, $name, $params, $returnType, $body);
    }

    private function injectJavaScriptInterpolatedVariables(string $route): string
    {
        // Regular expression pattern to match parameter placeholders
        $pattern = '/\{([^\/}]+)\}/';
        return preg_replace($pattern, '${$1}', $route);
    }

    private function convertToTypeScriptType(DtoType $dto, DtoList $dtoList): string
    {
        if ($dto->getExpressionType()->equals(ExpressionType::class())) {
            return sprintf("export type %s = {%s\n};", $dto->getName(), $this->convertToTypeScriptProperties($dto, $dtoList));
        }
        if ($dto->getExpressionType()->isAnyEnum()) {
            if ($this->shouldEnumBeConverterToUnion($dto)) {
                return sprintf("export type %s = %s;", $dto->getName(), $this->convertEnumToTypeScriptUnionProperties($dto->getProperties()));
            }
            return sprintf("export enum %s {%s\n}", $dto->getName(), $this->convertEnumToTypeScriptEnumProperties($dto->getProperties()));
        }
        throw new \Exception('Unknown expression type ' . $dto->getExpressionType()->jsonSerialize());
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

    private function convertToTypeScriptProperties(DtoType $dto, DtoList $dtoList): string
    {
        $string = '';

        /** @param DtoClassProperty[] $properties */
        $properties = $dto->getProperties();
        foreach ($properties as $property) {
            $string .= sprintf("\n  %s: %s;", $property->getName(), $this->getTypeScriptTypeFromPhp($property->getType(), $dto, $dtoList));
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

    private function getTypeScriptTypeFromPhp(PhpTypeInterface $type, DtoType|null $dto, DtoList $dtoList): string
    {
        if ($type instanceof PhpUnionType) {
            $types = array_map(fn (PhpTypeInterface $type) => $this->getTypeScriptTypeFromPhp($type, $dto, $dtoList), $type->getTypes());
            return implode(separator: ' | ', array: $types);
        }

        if ($type instanceof PhpListType) {
            return sprintf('%s[]', $this->getTypeScriptTypeFromPhp($type->getType(), $dto, $dtoList));
        }

        if ($type instanceof PhpBaseType) {
            /** @var PhpBaseType $type */
            return match (true) {
                $type->equalsTo(PhpBaseType::int()), $type->equalsTo(PhpBaseType::float()) => 'number',
                $type->equalsTo(PhpBaseType::string()) => 'string',
                $type->equalsTo(PhpBaseType::bool()) => 'boolean',
                $type->equalsTo(PhpBaseType::mixed()), $type->equalsTo(PhpBaseType::object()) => 'any',
                $type->equalsTo(PhpBaseType::array()), $type->equalsTo(PhpBaseType::iterable()) => 'any[]',
                $type->equalsTo(PhpBaseType::null()) => 'null',
                $type->equalsTo(PhpBaseType::self()) => $dto->getName(),
                default => throw new \Exception(sprintf("Unknown base PHP type: %s", $type->jsonSerialize()))
            };
        }

        /** @var PhpUnknownType $type */
        $result = $this->handleUnknownType($type, $dto, $dtoList);
        if ($result instanceof PhpTypeInterface) {
            return $this->getTypeScriptTypeFromPhp($result, $dto, $dtoList);
        }

        return $result;
    }

    private function handleUnknownType(PhpUnknownType $type, DtoType|null $dto, DtoList $dtoList): string|PhpTypeInterface
    {
        foreach ($this->unknownTypeResolvers as $unknownTypeResolver) {
            if ($unknownTypeResolver->supports($type, $dto, $dtoList)) {
                return $unknownTypeResolver->resolve($type, $dto, $dtoList);
            }
        }

        throw UnsupportedTypeException::forType($type, $dto?->getName() ?? '');
    }
}
