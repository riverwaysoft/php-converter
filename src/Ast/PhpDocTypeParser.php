<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
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

    public function parse(string $input): PhpTypeInterface|null
    {
        $tokens = new TokenIterator($this->lexer->tokenize($input));
        $result = $this->phpDocParser->parse($tokens)->children;
        /** @var TypeNode|null $varTagNode */
        $varTagNode = null;

        foreach ($result as $node) {
            if (!$node instanceof PhpDocTagNode) {
                continue;
            }

            if ($node->value instanceof VarTagValueNode) {
                $varTagNode = $node->value->type;
            }

            if ($node->value instanceof ReturnTagValueNode) {
                $varTagNode = $node->value->type;
            }
        }

        if (!$varTagNode) {
            return null;
        }

        return $this->convertToDto($varTagNode);
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
