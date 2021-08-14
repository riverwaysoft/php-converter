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
    ) {
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->createDtoType($node);
        }
    }

    private function createDtoType(Node\Stmt\Class_ $node): void
    {
        $createSingleType = function (Node\Name|Node\Identifier $param, ?string $docComment = null) {
            $typeName = get_class($param) === Node\Name::class ? $param->parts[0] : $param->name;
            if ($typeName === 'array' && $docComment) {
                return SingleType::array($this->parseArrayType($docComment));
            }
            return new SingleType($typeName);
        };

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
                    Node\UnionType::class => new UnionType(array_map($createSingleType, $stmt->type->types)),
                    Node\NullableType::class => UnionType::nullable($createSingleType($stmt->type->type)),
                    default => $createSingleType($stmt->type, $stmt->getDocComment()?->getText()),
                };

                $properties[] = new DtoClassProperty(
                    type: $type,
                    name: $stmt->props[0]->name->name,
                );
            }

            if ($stmt instanceof Node\Stmt\ClassMethod) {
                foreach ($stmt->params as $param) {
                    $type = match (get_class($param)) {
                        Node\UnionType::class => new UnionType(array_map($createSingleType, $param->types)),
                        Node\NullableType::class => UnionType::nullable($createSingleType($param->type->type)),
                        default => $createSingleType($param->type),
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
