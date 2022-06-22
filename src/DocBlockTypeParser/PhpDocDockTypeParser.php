<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\DocBlockTypeParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

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

    public function parse(string $input): PhpDocNode
    {
        return $this->phpDocParser->parse(new TokenIterator($this->lexer->tokenize($input)));
    }
}