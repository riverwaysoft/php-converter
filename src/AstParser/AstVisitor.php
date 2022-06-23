<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\AstParser;

use Riverwaysoft\DtoConverter\ClassFilter\ClassFilterInterface;
use Riverwaysoft\DtoConverter\Dto\DtoEnumProperty;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\DtoClassProperty;
use Riverwaysoft\DtoConverter\Dto\DtoType;
use Riverwaysoft\DtoConverter\Dto\ExpressionType;
use Riverwaysoft\DtoConverter\Dto\ListType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AstVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private DtoList $dtoList,
        private PhpDocTypeParser $phpDocTypeParser,
        private ?ClassFilterInterface $classFilter = null
    )
    {
    }

    /** @inheritDoc */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            if ($this->classFilter && !$this->classFilter->isMatch($node)) {
                return null;
            }
            $this->createDtoType($node);
        }

        return null;
    }

    private function createSingleType(
        Node\Name|Node\Identifier|Node\NullableType|Node\UnionType $param,
        ?string $docComment = null,
    ): SingleType|UnionType|ListType
    {
        if ($docComment) {
            $docBlockType = $this->phpDocTypeParser->parse($docComment);
            if ($docBlockType) {
                return $docBlockType;
            }
        }

        if ($param instanceof Node\UnionType) {
            return new UnionType(array_map(fn($singleParam) => $this->createSingleType($singleParam, $docComment), $param->types));
        }

        if ($param instanceof Node\NullableType) {
            return UnionType::nullable($this->createSingleType($param->type, $docComment));
        }

        $typeName = get_class($param) === Node\Name::class || get_class($param) === Node\Name\FullyQualified::class
            ? $param->parts[0]
            : $param->name;

        return new SingleType($typeName);
    }

    private function createDtoType(Node\Stmt\Class_ $node): void
    {
        $properties = [];
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassConst) {
                $propertyName = $stmt->consts[0]->name->name;
                /** @var string|number|null $notNullValue */
                $notNullValue = $stmt->consts[0]->value->value ?? null;
                $isNullValue = ($stmt->consts[0]->value->name->parts[0] ?? null) === 'null';
                if ($notNullValue === null && $isNullValue === false) {
                    throw new \Exception(sprintf("Property %s of enum is different from number, string and null.", $propertyName));
                }
                $properties[] = new DtoEnumProperty(
                    name: $propertyName,
                    value: $notNullValue === null ? null : $notNullValue,
                );
            }

            if ($stmt instanceof Node\Stmt\Property) {
                if ($stmt->type === null) {
                    throw new \Exception(sprintf("Property %s of class %s has no type. Please add PHP type", $stmt->props[0]->name->name, $node->name->name));
                }

                $type = $this->createSingleType($stmt->type, $stmt->getDocComment()?->getText());

                $properties[] = new DtoClassProperty(
                    type: $type,
                    name: $stmt->props[0]->name->name,
                );
            }

            if ($stmt instanceof Node\Stmt\ClassMethod) {
                foreach ($stmt->params as $param) {
                    if ($param->flags !== Node\Stmt\Class_::MODIFIER_PUBLIC) {
                        continue;
                    }

                    if ($param->type === null) {
                        throw new \Exception(sprintf("Property %s of class %s has no type. Please add PHP type", $param->var->name, $node->name->name));
                    }
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
}
