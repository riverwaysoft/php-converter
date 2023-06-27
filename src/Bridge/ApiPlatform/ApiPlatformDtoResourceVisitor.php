<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Bridge\ApiPlatform;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\PrettyPrinter\Standard;
use Riverwaysoft\DtoConverter\Ast\ConverterResult;
use Riverwaysoft\DtoConverter\Ast\ConverterVisitor;
use Riverwaysoft\DtoConverter\Bridge\Symfony\SymfonyRoutingParser;
use Riverwaysoft\DtoConverter\ClassFilter\ClassFilterInterface;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointMethod;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointParam;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeFactory;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;

class ApiPlatformDtoResourceVisitor extends ConverterVisitor
{
    private ApiPlatformIriGenerator $iriGenerator;
    private ConverterResult $converterResult;
    private Standard $prettyPrinter;
    public const API_PLATFORM_ATTRIBUTE = 'ApiResource';
    // Is used to wrap output types in CollectionResponse<T>
    public const COLLECTION_RESPONSE_CONTEXT_KEY = 'isCollectionResponse';

    public function __construct(private ?ClassFilterInterface $classFilter = null)
    {
        $this->converterResult = new ConverterResult();
        $this->iriGenerator = new ApiPlatformIriGenerator();
        $this->prettyPrinter = new Standard();
    }

    public function leaveNode(Node $node)
    {
        if (!$node instanceof Class_ && !$node instanceof Enum_) {
            return null;
        }

        if ($this->classFilter && !$this->classFilter->isMatch($node)) {
            return null;
        }

        $apiResourceAttribute = $this->findAttribute($node, self::API_PLATFORM_ATTRIBUTE);
        if (!$apiResourceAttribute) {
            throw new \Exception(sprintf('Class %s does not have #[%s] attribute', $node->name->name, self::API_PLATFORM_ATTRIBUTE));
        }


        // Support for legacy ApiResource annotation
        $legacyCollectionOperationsArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'collectionOperations');
        $legacyItemOperationsArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'itemOperations');
        // Support for new ApiResource annotation
        $operationsArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'operations');

        if (
                !$legacyCollectionOperationsArg?->value instanceof Node\Expr\Array_
                && !$legacyItemOperationsArg?->value instanceof Node\Expr\Array_
                && !$operationsArg?->value instanceof Node\Expr\Array_
            ) {
            return null;
        }

        // Main output
        $defaultOutput = null;
        $outputNode = $this->getAttributeArgumentByName($apiResourceAttribute, 'output');
        if ($outputNode?->value instanceof Node\Expr\ClassConstFetch) {
            $defaultOutput = $outputNode->value->class->parts[array_key_last($outputNode->value->class->parts)];
        }
        if (!$defaultOutput) {
            throw new \Exception(sprintf("The output is required for ApiResource %s. Context: %s", $node->name->name, $this->prettyPrinter->prettyPrint([$apiResourceAttribute])));
        }

        // Main input
        $defaultInput = null;
        $inputNode = $this->getAttributeArgumentByName($apiResourceAttribute, 'input');
        if ($inputNode?->value instanceof Node\Expr\ClassConstFetch) {
            $defaultInput = $inputNode->value->class->parts[array_key_last($inputNode->value->class->parts)];
        }

        if ($legacyCollectionOperationsArg?->value instanceof Node\Expr\Array_) {
            foreach ($legacyCollectionOperationsArg->value->items as $item) {
                $apiEndpoint = $this->createApiEndpointFromLegacyCode($item, true, $node, $defaultOutput, $defaultInput);
                if ($apiEndpoint) {
                    $this->converterResult->apiEndpointList->add($apiEndpoint);
                }
            }
        }

        if ($legacyItemOperationsArg?->value instanceof Node\Expr\Array_) {
            foreach ($legacyItemOperationsArg->value->items as $item) {
                $apiEndpoint = $this->createApiEndpointFromLegacyCode($item, false, $node, $defaultOutput, $defaultInput);
                if ($apiEndpoint) {
                    $this->converterResult->apiEndpointList->add($apiEndpoint);
                }
            }
        }

        if ($operationsArg?->value instanceof Node\Expr\Array_) {
            foreach ($operationsArg->value->items as $item) {
                $apiEndpoint = $this->createApiEndpoint($item, $node, $defaultOutput, $defaultInput);
                if ($apiEndpoint) {
                    $this->converterResult->apiEndpointList->add($apiEndpoint);
                }
            }
        }

        return null;
    }

    private function createApiEndpoint(Node\Expr\ArrayItem $item, Class_ $node, string $defaultOutput, string|null $defaultInput): null|ApiEndpoint
    {
        if (!$item->value instanceof Node\Expr\New_) {
            return null;
        }
        $classString = $item->value->class->parts[array_key_last($item->value->class->parts)];
        $isCollection = $classString === 'GetCollection';

        $method = match ($classString) {
            'GetCollection' => ApiEndpointMethod::get(),
            default => ApiEndpointMethod::fromString($classString),
        };

        $maybeInput = $this->getNewExpressionArgumentByName($item->value, 'input');
        $localInputClass = null;
        if ($maybeInput?->value instanceof Node\Expr\ClassConstFetch) {
            $localInputClass = $maybeInput->value->class->parts[array_key_last($maybeInput->value->class->parts)];
        }
        if ($localInputClass || $defaultInput) {
            $inputParam = new ApiEndpointParam('body', PhpTypeFactory::create($localInputClass ?? $defaultInput));
        } else {
            $inputParam = null;
        }

        $maybeOutput = $this->getNewExpressionArgumentByName($item->value, 'output');
        $localOutputClass = null;
        if ($maybeOutput?->value instanceof Node\Expr\ClassConstFetch) {
            $localOutputClass = $maybeOutput->value->class->parts[array_key_last($maybeOutput->value->class->parts)];
        }
        $outputTypeContext = $isCollection ? [self::COLLECTION_RESPONSE_CONTEXT_KEY => true] : [];
        $outputType = PhpTypeFactory::create($localOutputClass ?? $defaultOutput, $outputTypeContext);

        $queryParams = [];
        $routeParams = [];
        if ($isCollection) {
            $queryParams[] = new ApiEndpointParam('filters', new PhpOptionalType(PhpBaseType::object()));
        } else {
            $routeParams[] = new ApiEndpointParam('id', PhpBaseType::string());
        }

        $maybePath = $this->getNewExpressionArgumentByName($item->value, 'uriTemplate');
        if ($maybePath?->value instanceof Node\Scalar\String_) {
            $route = $maybePath->value->value;
            if (str_contains($route, '.{_format}')) {
                throw new \Exception(sprintf("Routes with .{_format} are not supported. Route %s", $route));
            }
            $routeParams = array_map(
                fn (string $param) => new ApiEndpointParam($param, PhpBaseType::string()),
                SymfonyRoutingParser::parseRoute($route),
            );
        } else {
            // If the 'route' is missed - generate it by ourselves
            $route = $this->iriGenerator->generate($node->name->name);
            if (!$isCollection) {
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

    private function createApiEndpointFromLegacyCode(Node\Expr\ArrayItem $item, bool $isCollection, Class_ $node, string $defaultOutput, string|null $defaultInput): null|ApiEndpoint
    {
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
            // If the 'route' is missed - generate it by ourselves
            $route = $this->iriGenerator->generate($node->name->name);
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
        $outputTypeContext = $isCollection && $method->equals(ApiEndpointMethod::get()) ? [self::COLLECTION_RESPONSE_CONTEXT_KEY => true] : [];
        $outputType = PhpTypeFactory::create($output, $outputTypeContext);

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
                    return $arrayItem->value->class->parts[array_key_last($arrayItem->value->class->parts)];
                }
                // Handle ['input' => false]
                if ($arrayItem->value instanceof Node\Expr\ConstFetch) {
                    if ($arrayItem->value->name->parts[array_key_last($arrayItem->value->name->parts)] === 'false') {
                        return null;
                    }
                }

                throw new \Exception('Expected to have string value for key '.$arrayItem->key->value);
            }
        }

        return null;
    }

    private function findAttribute(Class_ $node, string $name): Node\Attribute|null
    {
        $attrGroups = $node->attrGroups;

        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (in_array(needle: $name, haystack: $attr->name->parts)) {
                    return $attr;
                }
            }
        }

        return null;
    }

    private function getNewExpressionArgumentByName(Node\Expr\New_ $new, string $name): ?Node\Arg
    {
        foreach ($new->args as $arg) {
            if ($arg->name?->name === $name) {
                return $arg;
            }
        }

        return null;
    }

    private function getAttributeArgumentByName(Attribute $attribute, string $name): ?Node\Arg
    {
        foreach ($attribute->args as $arg) {
            if ($arg->name?->name === $name) {
                return $arg;
            }
        }

        return null;
    }

    public function popResult(): ConverterResult
    {
        $result = $this->converterResult;
        $this->converterResult = new ConverterResult();
        return $result;
    }
}
