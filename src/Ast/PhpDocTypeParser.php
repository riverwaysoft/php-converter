<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Ast;

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
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeFactory;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;

class PhpDocTypeParser
{
    private Lexer $lexer;
    private PhpDocParser $phpDocParser;

    public function __construct(private PhpTypeFactory $phpTypeFactory)
    {
        $this->lexer = new Lexer();
        $constExprParser = new ConstExprParser();
        $typeParser = new TypeParser($constExprParser);
        $this->phpDocParser = new PhpDocParser($typeParser, $constExprParser);
    }

    public function parse(string $input): PhpTypeInterface|null
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

    private function convertToDto(TypeNode $node): PhpTypeInterface|null
    {
        if ($node instanceof IdentifierTypeNode) {
            return $this->phpTypeFactory->create($node->name);
        }
        if ($node instanceof ArrayTypeNode) {
            return new PhpListType($this->convertToDto($node->type));
        }
        if ($node instanceof UnionTypeNode) {
            return new PhpUnionType(array_map(fn (TypeNode $child) => $this->convertToDto($child), $node->types));
        }
        return null;
    }
}
