<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\NodeTraverser;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\DtoEnumProperty;
use Riverwaysoft\PhpConverter\Dto\DtoType;
use Riverwaysoft\PhpConverter\Dto\ExpressionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeFactory;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use Riverwaysoft\PhpConverter\Filter\FilterInterface;
use function array_map;
use function get_class;
use function sprintf;

class DtoVisitor extends ConverterVisitor
{
    private PhpDocTypeParser $phpDocTypeParser;

    private ConverterResult $converterResult;

    public function __construct(
        private ?FilterInterface $filter = null
    ) {
        $this->phpDocTypeParser = new PhpDocTypeParser();
        $this->converterResult = new ConverterResult();
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof Class_ && !$node instanceof Enum_) {
            return null;
        }
        if ($this->filter && !$this->filter->isMatch($node)) {
            return null;
        }

        $this->createDtoType($node);

        return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
    }

    private function createDtoType(Class_|Enum_ $node): void
    {
        $properties = [];
        $expressionType = $this->resolveExpressionType($node);
        $classComments = $node->getDocComment()?->getText();

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassConst) {
                if ($expressionType->equals(ExpressionType::class())) {
                    continue;
                }
                $propertyName = $stmt->consts[0]->name->name;
                /** @var string|number|null $notNullValue */
                $notNullValue = $stmt->consts[0]->value->value ?? null;
                $isNullValue = ($stmt->consts[0]->value->name->parts[0] ?? null) === 'null';
                if ($notNullValue === null && $isNullValue === false) {
                    throw new Exception(sprintf("Property %s of enum is different from number, string and null.", $propertyName));
                }
                $properties[] = new DtoEnumProperty(
                    name: $propertyName,
                    value: $notNullValue === null ? null : $notNullValue,
                );
            }

            if ($stmt instanceof Node\Stmt\Property) {
                $comment = $stmt->getDocComment()?->getText() ?? '';

                /** @var PhpTypeInterface|null $docBlockType */
                $docBlockType = null;
                $nativeType = $stmt->type;
                $propertyName = $stmt->props[0]->name->name;

                if ($comment) {
                    $docBlockType = $this->phpDocTypeParser->parseVarOrReturn($comment);
                }

                if (!$nativeType && !$docBlockType) {
                    throw new Exception(sprintf("Property %s#%s has no type. Please add PHP type", $node->name->name, $propertyName));
                }

                $hasDefaultValue = !!$stmt->props[0]->default;

                $type = $docBlockType ?? $this->createSingleType($nativeType, '', $hasDefaultValue);

                $properties[] = new DtoClassProperty(
                    type: $type,
                    name: $propertyName,
                );
            }

            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $classMethodComments = $stmt->getDocComment()?->getText();
                /** @var DtoClassProperty[]|null $classMethodCommentsParsed */
                $classMethodCommentsParsed = null;
                foreach ($stmt->params as $param) {
                    if ($param->flags !== Node\Stmt\Class_::MODIFIER_PUBLIC) {
                        continue;
                    }

                    $paramType = $param->type;
                    $paramName = $param->var->name;
                    $hasDefaultValue = !!$param->default;

                    if ($classMethodComments) {
                        if ($classMethodCommentsParsed === null) {
                            $classMethodCommentsParsed = $this->phpDocTypeParser->parseMethodParams($classMethodComments);
                        }

                        foreach ($classMethodCommentsParsed as $classMethodCommentParsed) {
                            if ($classMethodCommentParsed->getName() === $paramName) {
                                $properties[] = $classMethodCommentParsed;
                                continue 2;
                            }
                        }
                    }

                    if ($paramType === null) {
                        throw new Exception(sprintf("Property %s#%s has no type. Please add PHP type", $node->name->name, $paramName));
                    }

                    $type = $this->createSingleType($paramType, $param->getDocComment()?->getText(), $hasDefaultValue);

                    $properties[] = new DtoClassProperty(
                        type: $type,
                        name: $paramName,
                    );
                }
            }

            if (!($stmt instanceof Node\Stmt\EnumCase)) {
                continue;
            }

            $expr = $stmt->expr;
            $propertyName = $stmt->name->name;
            if (!$expr) {
                throw new Exception(sprintf("Non-backed enums are not supported because they are not serializable. Please use backed enums: %s\n Error in enum: %s", 'https://www.php.net/manual/en/language.enumerations.backed.php', $propertyName));
            }
            if (!$expr instanceof Node\Scalar\LNumber && !$expr instanceof Node\Scalar\String_) {
                throw new Exception(sprintf('A backed enum should be type of int or string, %s given. Error in enum %s', get_class($expr), $propertyName));
            }
            $propertyValue = $expr->value;
            $properties[] = new DtoEnumProperty(
                name: $propertyName,
                value: $propertyValue,
            );
        }

        /** @var PhpUnknownType[] $generics */
        $generics = [];
        if ($classComments) {
            $generics = $this->phpDocTypeParser->parseClassComments($classComments);
        }

        $this->converterResult->dtoList->add(new DtoType(
            name: $node->name->name,
            expressionType: $expressionType,
            properties: $properties,
            generics: $generics,
        ));
    }

    private function createSingleType(
        Node\Name|Node\Identifier|Node\NullableType|Node\UnionType $param,
        ?string $docComment = null,
        bool $hasDefaultValue = false,
    ): PhpTypeInterface {
        if ($hasDefaultValue) {
            return new PhpOptionalType($this->createSingleType($param, $docComment));
        }

        if ($docComment) {
            $docBlockType = $this->phpDocTypeParser->parseVarOrReturn($docComment);
            if ($docBlockType) {
                return $docBlockType;
            }
        }

        if ($param instanceof Node\UnionType) {
            return new PhpUnionType(array_map(fn ($singleParam) => $this->createSingleType($singleParam), $param->types));
        }

        if ($param instanceof Node\NullableType) {
            return PhpUnionType::nullable($this->createSingleType($param->type));
        }

        $typeName = get_class($param) === Node\Name::class || get_class($param) === Node\Name\FullyQualified::class
            ? $param->getParts()[0]
            : $param->name;

        return PhpTypeFactory::create($typeName);
    }

    public function resolveExpressionType(Class_|Enum_ $node): ExpressionType
    {
        $isPhpBuiltInEnum = $node instanceof Enum_;
        if ($isPhpBuiltInEnum) {
            return ExpressionType::enum();
        }

        // https://github.com/myclabs/php-enum
        $isMyCLabsEnum = $node->extends?->getParts()[0] === 'Enum';

        return $isMyCLabsEnum
            ? ExpressionType::enumNonStandard()
            : ExpressionType::class();
    }

    public function getResult(): ConverterResult
    {
        return $this->converterResult;
    }
}
