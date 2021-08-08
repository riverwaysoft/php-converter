<?php

declare(strict_types=1);

namespace App;

use App\Dto\DtoList;
use App\Dto\DtoProperty;
use App\Dto\DtoType;
use App\Dto\SingleType;
use App\Dto\UnionType;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AstVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private DtoList $dtoList,
    )
    {
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            if ($this->hasTypeScriptAttribute($node)) {
                $this->createTypeScriptType($node);
            }
        }
    }

    private function hasTypeScriptAttribute(Node\Stmt\Class_ $node): bool
    {
        foreach ($node->attrGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                if (in_array(needle: 'Dto', haystack: $attr->name->parts)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function createTypeScriptType(Node\Stmt\Class_ $node)
    {
        $name = $node->name->name;

        $properties = [];
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Property) {
                $properties[] = new DtoProperty(
                    type: $stmt->type instanceof Node\UnionType
                    ? new UnionType(array_map(fn($type) => new SingleType($type->name), $stmt->type->types))
                    : new SingleType($stmt->type->name),
                    name: $stmt->props[0]->name->name,
                );
            }
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                foreach ($stmt->params as $param) {
                    $properties[] = new DtoProperty(
                        type: $param instanceof Node\UnionType
                        ? new UnionType(array_map(fn($type) => new SingleType($type->name), $param->types))
                        : new SingleType($param->type->name),
                        name: $param->var->name,
                    );
                }
            }
        }

        $this->dtoList->addDto(new DtoType(title: $name, properties: $properties));
    }
}
