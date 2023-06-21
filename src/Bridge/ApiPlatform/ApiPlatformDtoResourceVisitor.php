<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Bridge\ApiPlatform;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use Riverwaysoft\DtoConverter\Ast\ConverterResult;
use Riverwaysoft\DtoConverter\Ast\ConverterVisitor;
use Riverwaysoft\DtoConverter\ClassFilter\ClassFilterInterface;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointList;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointMethod;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeFactory;

class ApiPlatformDtoResourceVisitor extends ConverterVisitor
{
    private ApiPlatformIriGenerator $iriGenerator;
    private ConverterResult $converterResult;
    const API_PLATFORM_ATTRIBUTE = 'ApiResource';

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
            if ($collectionOperationsArg?->value instanceof Node\Expr\Array_) {
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

                foreach ($collectionOperationsArg->value->items as $item) {
                    if (!($item->value instanceof Node\Expr\Array_)) {
                        continue;
                    }
                    if (!($item->key instanceof Node\Scalar\String_)) {
                        continue;
                    }

                    $method = $this->findArrayAttributeValueByKey('method', $item->value->items) ?? $item->key->value;
                    $method = ApiEndpointMethod::fromString($method);

                    $route = $this->findArrayAttributeValueByKey('path', $item->value->items) ?? $this->iriGenerator->generate($node->name->name);
                    $route = '/api/'.ltrim($route, '/');

                    $output = $this->findArrayAttributeValueByKey('output', $item->value->items) ?? $mainOutput;
                    $outputType = PhpTypeFactory::create($output);

                    $input = $this->findArrayAttributeValueByKey('input', $item->value->items) ?? $mainInput;
                    $inputType = $input !== null ? PhpTypeFactory::create($input) : null;

                    $this->converterResult->apiEndpointList->add(new ApiEndpoint(
                        route: $route,
                        method: $method,
                        input: $inputType,
                        output: $outputType,
                        routeParams: [],
                    ));
                }
            }
        }

        return null;
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