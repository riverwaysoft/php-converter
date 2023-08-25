<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Bridge\ApiPlatform;

use Exception;
use Jawira\CaseConverter\Convert;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter\Standard;
use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Ast\ConverterVisitor;
use Riverwaysoft\PhpConverter\Bridge\Symfony\SymfonyRoutingParser;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpointMethod;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpointParam;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeFactory;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\Filter\FilterInterface;
use function array_key_last;
use function array_map;
use function count;
use function ltrim;
use function rtrim;
use function sprintf;
use function str_replace;

class ApiPlatformDtoResourceVisitor extends ConverterVisitor
{
    private ApiPlatformIriGenerator $iriGenerator;

    private ConverterResult $converterResult;

    private Standard $prettyPrinter;

    public const API_PLATFORM_ATTRIBUTE = 'ApiResource';

    public const RESPONSE_TYPE_NAME = 'CollectionResponse';

    public function __construct(
        private ?FilterInterface $filter = null
    ) {
        $this->converterResult = new ConverterResult();
        $this->iriGenerator = new ApiPlatformIriGenerator();
        $this->prettyPrinter = new Standard();
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof Class_ && !$node instanceof Enum_) {
            return null;
        }

        if ($this->filter && !$this->filter->isMatch($node)) {
            return null;
        }

        $apiResourceAttributes = $this->findAttributes($node, self::API_PLATFORM_ATTRIBUTE);
        if (empty($apiResourceAttributes)) {
            throw new Exception(sprintf('Class %s does not have #[%s] attribute', $node->name->name, self::API_PLATFORM_ATTRIBUTE));
        }

        foreach ($apiResourceAttributes as $apiResourceAttribute) {
            // Support for legacy ApiResource annotation
            $legacyCollectionOperationsArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'collectionOperations');
            $legacyItemOperationsArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'itemOperations');
            // Support for new ApiResource annotation
            $operationsArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'operations');
            $uriTemplateArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'uriTemplate');
            $shortNameArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'shortName');
            $forceSubresourcePath = null;

            if (
                !$legacyCollectionOperationsArg?->value instanceof Node\Expr\Array_
                && !$legacyItemOperationsArg?->value instanceof Node\Expr\Array_
                && !$operationsArg?->value instanceof Node\Expr\Array_
            ) {
                return null;
            }

            $shortName = null;
            if ($shortNameArg?->value instanceof Node\Scalar\String_) {
                $shortName = $shortNameArg->value->value;
            }

            // Main output
            $defaultOutput = null;
            $outputNode = $this->getAttributeArgumentByName($apiResourceAttribute, 'output');
            if ($outputNode?->value instanceof Node\Expr\ClassConstFetch) {
                $defaultOutput = $outputNode->value->class->getParts()[array_key_last($outputNode->value->class->getParts())];
            }

            // Main input
            $defaultInput = null;
            $inputNode = $this->getAttributeArgumentByName($apiResourceAttribute, 'input');
            if ($inputNode?->value instanceof Node\Expr\ClassConstFetch) {
                $defaultInput = $inputNode->value->class->getParts()[array_key_last($inputNode->value->class->getParts())];
            }

            if ($uriTemplateArg?->value instanceof Node\Scalar\String_) {
                // Remove Api Platform's subresource: https://api-platform.com/docs/core/subresources/
                $forceSubresourcePath = str_replace(search: '.{_format}', replace: '', subject: $uriTemplateArg->value->value);
            }

            if ($legacyCollectionOperationsArg?->value instanceof Node\Expr\Array_) {
                foreach ($legacyCollectionOperationsArg->value->items as $item) {
                    $apiEndpoint = $this->createApiEndpointFromLegacyCode($item, true, $node, $defaultOutput, $defaultInput, $shortName, $apiResourceAttribute);
                    if ($apiEndpoint) {
                        $this->converterResult->apiEndpointList->add($apiEndpoint);
                    }
                }
            }

            if ($legacyItemOperationsArg?->value instanceof Node\Expr\Array_) {
                foreach ($legacyItemOperationsArg->value->items as $item) {
                    $apiEndpoint = $this->createApiEndpointFromLegacyCode($item, false, $node, $defaultOutput, $defaultInput, $shortName, $apiResourceAttribute);
                    if ($apiEndpoint) {
                        $this->converterResult->apiEndpointList->add($apiEndpoint);
                    }
                }
            }

            if (!($operationsArg?->value instanceof Node\Expr\Array_)) {
                continue;
            }

            if ($forceSubresourcePath) {
                $endpointCount = count($operationsArg->value->items);
                if ($endpointCount !== 1) {
                    throw new Exception(sprintf('ApiResource %s should have only 1 array item in the "operations" field since it is subresource. %s given: ', $node->name->name, $endpointCount));
                }
            }

            foreach ($operationsArg->value->items as $item) {
                $apiEndpoint = $this->createApiEndpoint($item, $node, $defaultOutput, $defaultInput, $forceSubresourcePath, $shortName, $apiResourceAttribute);
                if ($apiEndpoint) {
                    $this->converterResult->apiEndpointList->add($apiEndpoint);
                }
            }
        }

        return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
    }

    private function createApiEndpoint(
        Node\Expr\ArrayItem $item,
        Class_ $node,
        string|null $defaultOutput,
        string|null $defaultInput,
        string|null $forcePath,
        string|null $shortName,
        Attribute $apiResourceAttribute,
    ): null|ApiEndpoint {
        if (!$item->value instanceof Node\Expr\New_) {
            return null;
        }
        $classString = $item->value->class->getParts()[array_key_last($item->value->class->getParts())];
        $isCollection = $classString === 'GetCollection';

        $method = match ($classString) {
            'GetCollection' => ApiEndpointMethod::get(),
            default => ApiEndpointMethod::fromString($classString),
        };

        $maybeInput = $this->getAttributeArgumentByName($item->value, 'input');
        $localInputClass = null;
        if ($maybeInput?->value instanceof Node\Expr\ClassConstFetch) {
            $localInputClass = $maybeInput->value->class->getParts()[array_key_last($maybeInput->value->class->getParts())];
        }
        if ($localInputClass || $defaultInput) {
            $inputParam = new ApiEndpointParam('body', PhpTypeFactory::create($localInputClass ?? $defaultInput));
        } else {
            $inputParam = null;
        }

        $maybeOutput = $this->getAttributeArgumentByName($item->value, 'output');
        $localOutputClass = null;
        if ($maybeOutput?->value instanceof Node\Expr\ClassConstFetch) {
            $localOutputClass = $maybeOutput->value->class->getParts()[array_key_last($maybeOutput->value->class->getParts())];
        }
        $output = $localOutputClass ?? $defaultOutput;
        if (!$output) {
            throw new Exception(sprintf("The output is required for ApiResource %s. Context: %s", $node->name->name, $this->prettyPrinter->prettyPrint([$apiResourceAttribute])));
        }

        $outputType = PhpTypeFactory::create($output);
        if ($isCollection) {
            $outputType = new PhpUnknownType(self::RESPONSE_TYPE_NAME, [
                $outputType,
            ], [
                PhpUnknownType::GENERIC_IGNORE_NO_RESOLVER => true,
            ]);
        }

        $queryParams = [];
        $routeParams = [];
        if ($isCollection) {
            $queryParams[] = new ApiEndpointParam('filters', new PhpOptionalType(PhpBaseType::object()));
        } else {
            if (!$method->equals(ApiEndpointMethod::post())) {
                $routeParams[] = new ApiEndpointParam('id', PhpBaseType::string());
            }
        }

        $route = $forcePath;
        if (!$route) {
            $maybePath = $this->getAttributeArgumentByName($item->value, 'uriTemplate');
            if ($maybePath?->value instanceof Node\Scalar\String_) {
                $route = $maybePath->value->value;
            }
        }

        if ($route) {
            $routeParams = array_map(
                fn (string $param) => new ApiEndpointParam($param, PhpBaseType::string()),
                SymfonyRoutingParser::parseRoute($route),
            );
        } else {
            $apiResourceName = $shortName ? (new Convert($shortName))->toKebab() : $node->name->name;
            // If the 'route' is missed - generate it by ourselves
            $route = $this->iriGenerator->generate($apiResourceName);
            if (!$isCollection && !$method->equals(ApiEndpointMethod::post())) {
                $route = sprintf("%s/{id}", rtrim($route, '/'));
                $routeParams = [new ApiEndpointParam('id', PhpBaseType::string())];
            }
        }

        $route = sprintf("/api/%s", ltrim($route, '/'));

        return new ApiEndpoint(
            route: $route,
            method: $method,
            input: $inputParam,
            output: $outputType,
            routeParams: $routeParams,
            queryParams: $queryParams,
        );
    }

    private function createApiEndpointFromLegacyCode(
        Node\Expr\ArrayItem $item,
        bool $isCollection,
        Class_ $node,
        string|null $defaultOutput,
        string|null $defaultInput,
        string|null $shortName,
        Attribute $apiResourceAttribute,
    ): null|ApiEndpoint {
        $arrayItemValue = $item->value instanceof Node\Expr\Array_ ? $item->value->items : null;

        $key = null;
        if ($item->key instanceof Node\Scalar\String_) {
            $key = $item->key->value;
        } else {
            if ($item->value instanceof Node\Scalar\String_) {
                $key = $item->value->value;
            }
        }

        if (!$key) {
            return null;
        }

        $method = $arrayItemValue ? ($this->findArrayAttributeValueByKey('method', $arrayItemValue) ?? $key) : $key;
        $method = ApiEndpointMethod::fromString($method);

        $route = $arrayItemValue ? $this->findArrayAttributeValueByKey('path', $arrayItemValue) : null;
        /** @var ApiEndpointParam[] $routeParams */
        $routeParams = [];
        /** @var ApiEndpointParam[] $queryParams */
        $queryParams = [];
        if (!$route) {
            $apiResourceName = $shortName ? (new Convert($shortName))->toKebab() : $node->name->name;
            // If the 'route' is missed - generate it by ourselves
            $route = $this->iriGenerator->generate($apiResourceName);
            if (!$isCollection) {
                $route = sprintf("%s/{id}", rtrim($route, '/'));
                $routeParams[] = new ApiEndpointParam('id', PhpBaseType::string());
            }
        } else {
            $routeParams = array_map(
                fn (string $param) => new ApiEndpointParam($param, PhpBaseType::string()),
                SymfonyRoutingParser::parseRoute($route),
            );
        }
        $route = sprintf("/api/%s", ltrim($route, '/'));

        // All API Platform GET methods can be filtered
        if ($isCollection && $method->equals(ApiEndpointMethod::get())) {
            $queryParams[] = new ApiEndpointParam('filters', new PhpOptionalType(PhpBaseType::object()));
        }

        $output = $arrayItemValue ? ($this->findArrayAttributeValueByKey('output', $arrayItemValue) ?? $defaultOutput) : $defaultOutput;
        if (!$output) {
            throw new Exception(sprintf("The output is required for ApiResource %s. Context: %s", $node->name->name, $this->prettyPrinter->prettyPrint([$apiResourceAttribute])));
        }

        $outputType = PhpTypeFactory::create($output);
        if ($isCollection && $method->equals(ApiEndpointMethod::get())) {
            $outputType = new PhpUnknownType(self::RESPONSE_TYPE_NAME, [
                $outputType,
            ], [
                PhpUnknownType::GENERIC_IGNORE_NO_RESOLVER => true,
            ]);
        }

        $input = null;
        if ($arrayItemValue) {
            $input = $this->findArrayAttributeValueByKey('input', $arrayItemValue) ?? $defaultInput;
        } else {
            if (!$method->equals(ApiEndpointMethod::get())) {
                $input = $defaultInput;
            }
        }

        $inputType = $input !== null ? new ApiEndpointParam('body', PhpTypeFactory::create($input)) : null;

        return new ApiEndpoint(
            route: $route,
            method: $method,
            input: $inputType,
            output: $outputType,
            routeParams: $routeParams,
            queryParams: $queryParams,
        );
    }

    /**
     * @param Node\Expr\ArrayItem[] $arrayItems
     */
    private function findArrayAttributeValueByKey(string $key, array $arrayItems): string|null
    {
        foreach ($arrayItems as $arrayItem) {
            if ($arrayItem->key instanceof Node\Scalar\String_ && $arrayItem->key->value === $key) {
                if ($arrayItem->value instanceof Node\Scalar\String_) {
                    return $arrayItem->value->value;
                }
                if ($arrayItem->value instanceof Node\Expr\ClassConstFetch) {
                    return $arrayItem->value->class->getParts()[array_key_last($arrayItem->value->class->getParts())];
                }
                // Handle ['input' => false]
                if ($arrayItem->value instanceof Node\Expr\ConstFetch) {
                    if ($arrayItem->value->name->getParts()[array_key_last($arrayItem->value->name->getParts())] === 'false') {
                        return null;
                    }
                }

                throw new Exception('Expected to have string value for key ' . $arrayItem->key->value);
            }
        }

        return null;
    }

    /** @return Node\Attribute[] */
    private function findAttributes(Class_ $node, string $name): array
    {
        $attrGroups = $node->attrGroups;
        /** @var Node\Attribute[] $result */
        $result = [];

        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($name === $attr->name->getLast()) {
                    $result[] = $attr;
                }
            }
        }

        return $result;
    }

    private function getAttributeArgumentByName(Attribute|Node\Expr\New_ $attribute, string $name): ?Node\Arg
    {
        foreach ($attribute->args as $arg) {
            if ($arg->name?->name === $name) {
                return $arg;
            }
        }

        return null;
    }

    public function getResult(): ConverterResult
    {
        return $this->converterResult;
    }
}
