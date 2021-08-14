<?php

declare(strict_types=1);

namespace App\Ast;

use App\Dto\DtoEnumProperty;
use App\Dto\DtoList;
use App\Dto\DtoClassProperty;
use App\Dto\DtoType;
use App\Dto\ExpressionType;
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
            $this->createDtoType($node);
        }
    }

    private function createSingleType(Node\Name|Node\Identifier|Node\NullableType $param, ?string $docComment = null): SingleType|UnionType
    {
        if ($param instanceof Node\NullableType) {
            return UnionType::nullable($this->createSingleType($param->type));
        }

        $typeName = get_class($param) === Node\Name::class || get_class($param) === Node\Name\FullyQualified::class
            ? $param->parts[0]
            : $param->name;

        if ($typeName === 'array' && $docComment) {
            return SingleType::array($this->parseArrayType($docComment));
        }

        return new SingleType($typeName);
    }

    private function createDtoType(Node\Stmt\Class_ $node): void
    {
        $properties = [];
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassConst) {
                $properties[] = new DtoEnumProperty(
                    name: $stmt->consts[0]->name->name,
                    value: $stmt->consts[0]->value->value,
                );
            }

            if ($stmt instanceof Node\Stmt\Property) {
                $type = match (get_class($stmt->type)) {
                    Node\UnionType::class => new UnionType(array_map([$this, 'createSingleType'], $stmt->type->types)),
                    default => $this->createSingleType($stmt->type, $stmt->getDocComment()?->getText()),
                };

                $properties[] = new DtoClassProperty(
                    type: $type,
                    name: $stmt->props[0]->name->name,
                );
            }

            if ($stmt instanceof Node\Stmt\ClassMethod) {
                foreach ($stmt->params as $param) {
                    $type = match (get_class($param)) {
                        Node\UnionType::class => new UnionType(array_map([$this, 'createSingleType'], $param->types)),
                        default => $this->createSingleType($param->type, $param->getDocComment()),
                    };

                    $properties[] = new DtoClassProperty(
                        type: $type,
                        name: $param->var->name,
                    );
                }
            }
        }

        $this->dtoList->addDto(new DtoType(
            name: $node->name->name,
            expressionType: $this->resolveExpressionType($node),
            properties: $properties,
        ));
    }

    public function resolveExpressionType(Node\Stmt\Class_ $node): ExpressionType
    {
        return ($node->extends?->parts[0] === 'Enum')
            ? ExpressionType::enum()
            : ExpressionType::class();
    }

    private function parseArrayType(string $docComment): string
    {
        preg_match('/var (.+)\[]/', $docComment, $matches);

        return $matches[1];
    }
}
