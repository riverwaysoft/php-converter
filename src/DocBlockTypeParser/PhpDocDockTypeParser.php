<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\DocBlockTypeParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Riverwaysoft\DtoConverter\Dto\ListType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;

class PhpDocDockTypeParser
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

        $firstNode = $result[0];
        if (!$firstNode instanceof PhpDocTagNode) {
            return null;
        }

        $varTagNode = $firstNode->value;
        if (!$varTagNode instanceof VarTagValueNode) {
            return null;
        }

        return $this->convertToDto($varTagNode->type);
    }

    private function convertToDto(TypeNode $node)
    {
        if ($node instanceof ArrayTypeNode) {
            $type = $node->type;
            if (!$type instanceof IdentifierTypeNode) {
                throw new \RuntimeException();
            }
            return new ListType(new SingleType($type->name));
        }
        return null;
    }
}