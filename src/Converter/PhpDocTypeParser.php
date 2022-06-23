<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Converter;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Riverwaysoft\DtoConverter\Dto\ListType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;

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

    public function parse(string $input): ListType|SingleType|UnionType|null
    {
        $result = $this->phpDocParser->parse(new TokenIterator($this->lexer->tokenize($input)))->children;
        if (!is_array($result)) {
            return null;
        }

        $varTagNode = null;

        foreach ($result as $node) {
            if (!$node instanceof PhpDocTagNode) {
                continue;
            }

            if ($node->value instanceof VarTagValueNode) {
                $varTagNode = $node->value;
            }
        }

        if (!$varTagNode) {
            return null;
        }

        return $this->convertToDto($varTagNode->type);
    }

    private function convertToDto(TypeNode $node)
    {
        if ($node instanceof IdentifierTypeNode) {
            return new SingleType($node->name);
        }
        if ($node instanceof ArrayTypeNode) {
            return new ListType($this->convertToDto($node->type));
        }
        if ($node instanceof UnionTypeNode) {
            return new UnionType(array_map(fn(TypeNode $child) => $this->convertToDto($child), $node->types));
        }
        return null;
    }
}