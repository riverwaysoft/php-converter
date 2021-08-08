<?php

declare(strict_types=1);

namespace App;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AstVisitor extends NodeVisitorAbstract
{
    private DtoList $dtoList;

    public function __construct()
    {
        $this->dtoList = new DtoList();
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
                    type: $stmt->type->name,
                    name: $stmt->props[0]->name->name,
                );
            }
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                foreach ($stmt->params as $param) {
                    $properties[] = new DtoProperty(
                        type: $param->type->name,
                        name: $param->var->name,
                    );
                }
            }
        }

        $this->dtoList->addDto(new DtoType(title: $name, properties: $properties));
    }

    public function getDtoList(): DtoList
    {
        return $this->dtoList;
    }
}
