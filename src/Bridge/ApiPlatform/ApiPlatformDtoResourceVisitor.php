<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Bridge\ApiPlatform;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use Riverwaysoft\DtoConverter\Ast\ConverterResult;
use Riverwaysoft\DtoConverter\Ast\ConverterVisitor;
use Riverwaysoft\DtoConverter\Bridge\Symfony\SymfonyRoutingParser;
use Riverwaysoft\DtoConverter\ClassFilter\ClassFilterInterface;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointMethod;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointParam;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeFactory;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;

class ApiPlatformDtoResourceVisitor extends ConverterVisitor
{
    private ApiPlatformIriGenerator $iriGenerator;
    private ConverterResult $converterResult;
    public const API_PLATFORM_ATTRIBUTE = 'ApiResource';
    // Is used to wrap output types in CollectionResponse<T>
    public const COLLECTION_RESPONSE_CONTEXT_KEY = 'isCollectionResponse';

    public function __construct(private ?ClassFilterInterface $classFilter = null)
    {
        $this->converterResult = new ConverterResult();
        $this->iriGenerator = new ApiPlatformIriGenerator();
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Class_ || $node instanceof Enum_) {
            if ($this->classFilter && !$this->classFilter->isMatch($node)) {
                return null;
            }

            $apiResourceAttribute = $this->findAttribute($node, self::API_PLATFORM_ATTRIBUTE);
            if (!$apiResourceAttribute) {
                throw new \Exception(sprintf('Class %s does not have #[%s] attribute', $node->name->name, self::API_PLATFORM_ATTRIBUTE));
            }

            $collectionOperationsArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'collectionOperations');
            $itemOperationsArg = $this->getAttributeArgumentByName($apiResourceAttribute, 'itemOperations');

            if ($collectionOperationsArg?->value instanceof Node\Expr\Array_ || $itemOperationsArg?->value instanceof Node\Expr\Array_) {
                // Main output
                $mainOutput = null;
                $outputNode = $this->getAttributeArgumentByName($apiResourceAttribute, 'output');
                if ($outputNode?->value instanceof Node\Expr\ClassConstFetch) {
                    $mainOutput = $outputNode->value->class->parts[array_key_last($outputNode->value->class->parts)];
                }
                if (!$mainOutput) {
                    throw new \Exception('Invalid output of ApiResource '. $node->name->name);
                }

                // Main input
                $mainInput = null;
                $inputNode = $this->getAttributeArgumentByName($apiResourceAttribute, 'input');
                if ($inputNode?->value instanceof Node\Expr\ClassConstFetch) {
                    $mainInput = $inputNode->value->class->parts[array_key_last($inputNode->value->class->parts)];
                }

                if ($collectionOperationsArg?->value instanceof Node\Expr\Array_) {
                    foreach ($collectionOperationsArg->value->items as $item) {
                        $apiEndpoint = $this->createApiEndpoint($item, true, $node, $mainOutput, $mainInput);
                        if ($apiEndpoint) {
                            $this->converterResult->apiEndpointList->add($apiEndpoint);
                        }
                    }
                }

                if ($itemOperationsArg?->value instanceof Node\Expr\Array_) {
                    foreach ($itemOperationsArg->value->items as $item) {
                        $apiEndpoint = $this->createApiEndpoint($item, false, $node, $mainOutput, $mainInput);
                        if ($apiEndpoint) {
                            $this->converterResult->apiEndpointList->add($apiEndpoint);
                        }
                    }
                }
            }
        }

        return null;
    }

    private function createApiEndpoint(Node\Expr\ArrayItem $item, bool $isCollection, Class_ $node, string $mainOutput, string|null $mainInput): null|ApiEndpoint
    {
        if (!($item->value instanceof Node\Expr\Array_)) {
            return null;
        }
        if (!($item->key instanceof Node\Scalar\String_)) {
            return null;
        }

        $method = $this->findArrayAttributeValueByKey('method', $item->value->items) ?? $item->key->value;
        $method = ApiEndpointMethod::fromString($method);

        $route = $this->findArrayAttributeValueByKey('path', $item->value->items);
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
            $queryParams[] = new ApiEndpointParam('filters', PhpBaseType::object());
        }

        $output = $this->findArrayAttributeValueByKey('output', $item->value->items) ?? $mainOutput;
        $outputTypeContext = $isCollection && $method->equals(ApiEndpointMethod::get()) ? [self::COLLECTION_RESPONSE_CONTEXT_KEY => true] : [];
        $outputType = PhpTypeFactory::create($output, $outputTypeContext);

        $input = $this->findArrayAttributeValueByKey('input', $item->value->items) ?? $mainInput;
        $inputType = $input !== null ? PhpTypeFactory::create($input) : null;

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
