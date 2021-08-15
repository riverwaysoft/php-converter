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

    /** @inheritDoc */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->createDtoType($node);
        }

        return null;
    }

    private function createSingleType(
        Node\Name|Node\Identifier|Node\NullableType|Node\UnionType $param,
        ?string $docComment = null,
    ): SingleType|UnionType {
        if ($param instanceof Node\UnionType) {
            return new UnionType(array_map([$this, 'createSingleType'], $param->types));
        }

        if ($param instanceof Node\NullableType) {
            return UnionType::nullable($this->createSingleType($param->type));
        }

        $typeName = get_class($param) === Node\Name::class || get_class($param) === Node\Name\FullyQualified::class
            ? $param->parts[0]
            : $param->name;

        if ($typeName === 'array' && $docComment) {
            return SingleType::list($this->parseArrayType($docComment));
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
                    /** @phpstan-ignore-next-line */
                    value: $stmt->consts[0]->value->value,
                );
            }

            if ($stmt instanceof Node\Stmt\Property) {
                $type = $this->createSingleType($stmt->type, $stmt->getDocComment()?->getText());

                $properties[] = new DtoClassProperty(
                    type: $type,
                    name: $stmt->props[0]->name->name,
                );
            }

            if ($stmt instanceof Node\Stmt\ClassMethod) {
                foreach ($stmt->params as $param) {
                    $type = $this->createSingleType($param->type, $param->getDocComment()?->getText());

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
