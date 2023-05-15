<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Ast;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointList;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointMethod;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeFactory;

class SymfonyControllerVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private string $attribute,
        private ApiEndpointList $apiEndpointList,
        private PhpTypeFactory $phpTypeFactory,
    ) {
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassMethod && $this->findAttribute($node, $this->attribute)) {
            $this->createApiEndpoint($node);
        }

        return null;
    }

    private function findAttribute(Node|Node\Param $node, string $name): Attribute|null
    {
        $attrGroups = $node instanceof Node\Param ? $node->attrGroups : $node->getAttrGroups();

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

    private function createApiEndpoint(ClassMethod $node): void
    {
        $routeAttribute = $this->findAttribute($node, 'Route');

        if (!$routeAttribute) {
            throw new \Exception('#[DtoEndpoint] is used on a method, that does not have #[Route] attribute');
        }

        $url = null;
        if ($routeAttribute->args[0]->name === null) {
            $urlNode = $routeAttribute->args[0]->value;
            if ($urlNode instanceof Node\Scalar\String_) {
                $url = $urlNode->value;
            }
        }

        $method = null;
        $methodArg = $this->getAttributeArgumentByName($routeAttribute, 'methods');
        if ($methodArg) {
            if ($methodArg->value instanceof Node\Expr\Array_) {
                if (count($methodArg->value->items) > 1) {
                    throw new \Exception('At the moment argument "methods" should have only 1 item');
                }
                $methodString = $methodArg->value->items[0]->value;
                if ($methodString instanceof Node\Scalar\String_) {
                    $method = $methodString->value;
                }
            } else {
                throw new \Exception('Only array argument "methods" is supported');
            }
        }

        if (!$method) {
            throw new \Exception('#[Route()] argument methods is required');
        }

        $dtoReturnAttribute = $this->findAttribute($node, 'DtoEndpoint');
        if (!$dtoReturnAttribute) {
            throw new \Exception('Should not be reached, checked earlier');
        }

        $outputType = null;
        if ($arg = $this->getAttributeArgumentByName($dtoReturnAttribute, 'returnOne')) {
            if (!($arg->value instanceof Node\Expr\ClassConstFetch)) {
                throw new \Exception('Argument of returnOne should be a class string');
            }
            $outputType = $this->phpTypeFactory->create($arg->value->class->parts[0]);
        }
        if ($arg = $this->getAttributeArgumentByName($dtoReturnAttribute, 'returnMany')) {
            if (!($arg->value instanceof Node\Expr\ClassConstFetch)) {
                throw new \Exception('Argument of returnMany should be a class string');
            }
            $outputType = new PhpListType($this->phpTypeFactory->create($arg->value->class->parts[0]));
        }

        $inputType = null;

        foreach ($node->params as $param) {
            $maybeDtoInputAttribute = $this->findAttribute($param, 'Input');
            if ($maybeDtoInputAttribute) {
                $inputType = $this->phpTypeFactory->create($param->type->parts[0]);
                break;
            }
        }

        $this->apiEndpointList->add(new ApiEndpoint(
            url: $url,
            method: ApiEndpointMethod::fromString($method),
            input: $inputType,
            output: $outputType,
        ));
    }
}
