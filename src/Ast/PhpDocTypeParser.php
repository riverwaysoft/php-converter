<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeFactory;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use function array_map;

class PhpDocTypeParser
{
    private Lexer $lexer;

    private PhpDocParser $phpDocParser;

    public function __construct()
    {
        $this->lexer = new Lexer();
        $constExprParser = new ConstExprParser();
        $typeParser = new TypeParser($constExprParser);
        $this->phpDocParser = new PhpDocParser($typeParser, $constExprParser);
    }

    /** @return DtoClassProperty[] */
    public function parseMethodParams(string $input): array
    {
        $phpDocNodes = $this->commentToPhpDocNodes($input);
        /** @var DtoClassProperty[] $results */
        $results = [];

        foreach ($phpDocNodes as $node) {
            if (!$node instanceof PhpDocTagNode) {
                continue;
            }

            if (!($node->value instanceof ParamTagValueNode)) {
                continue;
            }

            $convertedType = $this->convertToDto($node->value->type);
            $isParameterNameValid = str_starts_with($node->value->parameterName, '$') && mb_strlen($node->value->parameterName) > 1;
            if ($convertedType === null || !$isParameterNameValid) {
                continue;
            }

            $results[] = new DtoClassProperty(
                type: $convertedType,
                name: ltrim($node->value->parameterName, '$')
            );
        }

        return $results;
    }

    /** @return PhpTypeInterface[]  */
    public function parseClassComments(string $input): array
    {
        $phpDocNodes = $this->commentToPhpDocNodes($input);
        /** @var PhpTypeInterface[] $generics */
        $generics = [];

        foreach ($phpDocNodes as $node) {
            if (!$node instanceof PhpDocTagNode) {
                continue;
            }

            if (!($node->value instanceof TemplateTagValueNode)) {
                continue;
            }
            $generics[] = PhpTypeFactory::create($node->value->name);
        }

        return $generics;
    }

    /** @return PhpDocChildNode[] */
    private function commentToPhpDocNodes(string $input): array
    {
        $tokens = new TokenIterator($this->lexer->tokenize($input));
        return $this->phpDocParser->parse($tokens)->children;
    }

    public function parseVarOrReturn(string $input): PhpTypeInterface|null
    {
        $phpDocNodes = $this->commentToPhpDocNodes($input);

        foreach ($phpDocNodes as $node) {
            if (!$node instanceof PhpDocTagNode) {
                continue;
            }

            if (!($node->value instanceof VarTagValueNode) && !($node->value instanceof ReturnTagValueNode)) {
                continue;
            }

            return $this->convertToDto($node->value->type);
        }

        return null;
    }

    private function convertToDto(TypeNode $node): PhpTypeInterface|null
    {
        if ($node instanceof IdentifierTypeNode) {
            return PhpTypeFactory::create($node->name);
        }
        if ($node instanceof ArrayTypeNode) {
            return new PhpListType($this->convertToDto($node->type));
        }
        if ($node instanceof UnionTypeNode) {
            return new PhpUnionType(array_map(fn (TypeNode $child) => $this->convertToDto($child), $node->types));
        }
        if ($node instanceof GenericTypeNode) {
            return PhpTypeFactory::create($node->type->name, [], array_map(
                fn (TypeNode $child) => $this->convertToDto($child),
                $node->genericTypes,
            ));
        }
        return null;
    }
}
