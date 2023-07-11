<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Language\TypeScript;

use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpointParam;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\ExpressionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\Language\LanguageGeneratorInterface;
use Riverwaysoft\PhpConverter\Language\UnknownTypeResolver\UnknownTypeResolverInterface;
use Riverwaysoft\PhpConverter\Language\UnsupportedTypeException;
use Riverwaysoft\PhpConverter\OutputWriter\OutputFile;
use Riverwaysoft\PhpConverter\OutputWriter\OutputProcessor\OutputFilesProcessor;
use Riverwaysoft\PhpConverter\OutputWriter\OutputWriterInterface;
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
        $this->options = $options ?? new TypeScriptGeneratorOptions(useTypesInsteadOfEnums: false);
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
        $string = "\nexport const %s = (%s): %s => {\n%s\n}\n";

        $fullRoute = $apiEndpoint->route . '/' . $apiEndpoint->method->getType();
        $name = $this->normalizeEndpointName($fullRoute);

        $params = array_map(
            fn (ApiEndpointParam $param): string => "{$param->name}: {$this->getTypeScriptTypeFromPhp($param->type, null, $dtoList)}",
            $apiEndpoint->routeParams,
        );

        $inputType = null;
        if ($apiEndpoint->input) {
            $inputType = $this->getTypeScriptTypeFromPhp($apiEndpoint->input->type, null, $dtoList);
            $params = array_merge($params, ["{$apiEndpoint->input->name}: {$inputType}"]);
        }

        if (count($apiEndpoint->queryParams)) {
            $queryParamsAsTs = array_map(
                fn (ApiEndpointParam $param): string => "{$param->name}: {$this->getTypeScriptTypeFromPhp($param->type, null, $dtoList)}",
                $apiEndpoint->queryParams,
            );
            $params = array_merge($params, $queryParamsAsTs);
        }

        $params = implode(', ', array_filter($params));


        $formParams = [];
        if ($apiEndpoint->input) {
            $formParams[] = $apiEndpoint->input->name;
        }
        if ($apiEndpoint->queryParams) {
            $message = sprintf("Multiple query params are not supported. Context: %s", json_encode($apiEndpoint->queryParams));
            Assert::count($apiEndpoint->queryParams, 1, $message);
            $formParams[] = sprintf('{ params: %s }', $apiEndpoint->queryParams[0]->name);
        }
        $formParamsAsString = $formParams ? sprintf(", %s", implode(', ', $formParams)) : '';

        $outputType = $apiEndpoint->output ? $this->getTypeScriptTypeFromPhp($apiEndpoint->output, null, $dtoList) : 'null';

        $returnType = sprintf('Promise<%s>', $outputType);

        $route = $this->injectJavaScriptInterpolatedVariables($apiEndpoint->route);
        $body = sprintf('  return axios
    .%s<%s>(`%s`%s)
    .then((response) => response.data);', $apiEndpoint->method->getType(), $outputType, $route, $formParamsAsString);

        return sprintf($string, $name, $params, $returnType, $body);
    }

    private function normalizeEndpointName(string $str): string
    {
        // Remove slashes and braces
        $str = preg_replace('/[^a-zA-Z0-9]/', ' ', $str);
        // Convert to camel case
        $str = ucwords($str);
        // Remove spaces and convert the first character to lowercase
        return lcfirst(str_replace(' ', '', $str));
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
            $listType = $this->getTypeScriptTypeFromPhp($type->getType(), $dto, $dtoList);
            if ($type->getType() instanceof PhpUnionType) {
                return sprintf('(%s)[]', $listType);
            }
            return sprintf('%s[]', $listType);
        }

        if ($type instanceof PhpOptionalType) {
            return sprintf('%s | null = null', $this->getTypeScriptTypeFromPhp($type->getType(), $dto, $dtoList));
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
