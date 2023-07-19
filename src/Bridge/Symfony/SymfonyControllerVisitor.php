<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Bridge\Symfony;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use Riverwaysoft\PhpConverter\Ast\ConverterResult;
use Riverwaysoft\PhpConverter\Ast\ConverterVisitor;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpointMethod;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpointParam;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeFactory;
use Exception;

class SymfonyControllerVisitor extends ConverterVisitor
{
    private ConverterResult $converterResult;

    public function __construct(
        // TODO: consider using class filter interface?
        private string $attribute,
    ) {
        $this->converterResult = new ConverterResult();
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof ClassMethod || !$this->findAttribute($node, $this->attribute)) {
            return null;
        }

        $this->createApiEndpoint($node);

        return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
    }

    private function findAttribute(ClassMethod|Node\Param $node, string $name): Attribute|null
    {
        $attrGroups = $node instanceof Node\Param ? $node->attrGroups : $node->getAttrGroups();

        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (in_array(needle: $name, haystack: $attr->name->getParts())) {
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

    private function createApiEndpoint(ClassMethod $node): void
    {
        $routeAttribute = $this->findAttribute($node, 'Route');

        if (!$routeAttribute) {
            throw new Exception('#[DtoEndpoint] is used on a method, that does not have #[Route] attribute');
        }

        $route = null;
        if ($routeAttribute->args[0]->name === null) {
            $routeNode = $routeAttribute->args[0]->value;
            if ($routeNode instanceof Node\Scalar\String_) {
                $route = $routeNode->value;
            }
        }

        if (!$route) {
            $nameArg = $this->getAttributeArgumentByName($routeAttribute, 'name');
            if ($nameArg?->value instanceof Node\Scalar\String_) {
                $route = $nameArg->value->value;
            }
            $pathArg = $this->getAttributeArgumentByName($routeAttribute, 'path');
            if ($pathArg?->value instanceof Node\Scalar\String_) {
                $route = $pathArg->value->value;
            }

            if (!$route) {
                throw new Exception('Could not find route path. Make sure your route looks like this #[Route(\'/api/users\')] or #[Route(name: \'/api/users/\')] or #[Route(path: \'/api/users/\')]');
            }
        }

        $method = null;
        $methodArg = $this->getAttributeArgumentByName($routeAttribute, 'methods');
        if ($methodArg) {
            if ($methodArg->value instanceof Node\Expr\Array_) {
                if (count($methodArg->value->items) > 1) {
                    throw new Exception('At the moment argument "methods" should have only 1 item');
                }
                $methodString = $methodArg->value->items[0]->value;
                if ($methodString instanceof Node\Scalar\String_) {
                    $method = $methodString->value;
                }
            } elseif ($methodArg->value instanceof Node\Scalar\String_) {
                $method = $methodArg->value->value;
            } else {
                throw new Exception('Only array argument "methods" is supported');
            }
        }

        if (!$method) {
            throw new Exception('#[Route()] argument "methods" is required');
        }

        $dtoReturnAttribute = $this->findAttribute($node, 'DtoEndpoint');
        if (!$dtoReturnAttribute) {
            throw new Exception('Should not be reached, checked earlier');
        }

        $outputType = PhpBaseType::null();
        if ($arg = $this->getAttributeArgumentByName($dtoReturnAttribute, 'returnOne')) {
            if (!($arg->value instanceof Node\Expr\ClassConstFetch)) {
                throw new Exception('Argument of returnOne should be a class string');
            }
            $outputType = PhpTypeFactory::create($arg->value->class->getParts()[0]);
        }
        if ($arg = $this->getAttributeArgumentByName($dtoReturnAttribute, 'returnMany')) {
            if (!($arg->value instanceof Node\Expr\ClassConstFetch)) {
                throw new Exception('Argument of returnMany should be a class string');
            }
            $outputType = new PhpListType(PhpTypeFactory::create($arg->value->class->getParts()[0]));
        }

        $inputParam = null;
        /** @var ApiEndpointParam[] $queryParams */
        $queryParams = [];
        $routeParams = SymfonyRoutingParser::parseRoute($route);
        /** @var string[] $excessiveRouteParams */
        $excessiveRouteParams = array_flip($routeParams);
        foreach ($node->params as $param) {
            $maybeDtoInputAttribute = $this->findAttribute($param, 'Input');
            if ($maybeDtoInputAttribute) {
                if ($inputParam) {
                    throw new Exception('Multiple #[Input] on controller action are not supported');
                }
                $inputParam = new ApiEndpointParam(
                    name: $param->var->name,
                    // TODO: class names with full namespace probably aren't supported
                    type: PhpTypeFactory::create($param->type->getParts()[0]),
                );
            }
            $maybeDtoQueryAttribute = $this->findAttribute($param, 'Query');
            if ($maybeDtoQueryAttribute) {
                if (!empty($queryParams)) {
                    throw new Exception('Multiple #[Query] on controller action are not supported');
                }
                $queryParams[] = new ApiEndpointParam(
                    name: $param->var->name,
                    // TODO: class names with full namespace probably aren't supported
                    type: PhpTypeFactory::create($param->type->getParts()[0]),
                );
            }

            if (isset($excessiveRouteParams[$param->var->name])) {
                unset($excessiveRouteParams[$param->var->name]);
            }
        }

        if (!empty($excessiveRouteParams)) {
            throw new Exception(sprintf(
                'Route %s has parameter %s, but there are no method params with this name. Available parameters: %s',
                $route,
                array_key_first($excessiveRouteParams),
                implode(', ', array_map(
                    fn (Node\Param $param): string => $param->var->name,
                    $node->params,
                ))
            ));
        }

        $this->converterResult->apiEndpointList->add(new ApiEndpoint(
            route: $route,
            method: ApiEndpointMethod::fromString($method),
            input: $inputParam,
            output: $outputType,
            routeParams: array_map(
                fn (string $paramName) => new ApiEndpointParam($paramName, PhpBaseType::string()),
                $routeParams,
            ),
            queryParams: $queryParams,
        ));
    }

    public function popResult(): ConverterResult
    {
        $result = $this->converterResult;
        $this->converterResult = new ConverterResult();
        return $result;
    }
}
