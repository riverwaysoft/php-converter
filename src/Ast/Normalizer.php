<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Ast;

use Riverwaysoft\DtoConverter\Ast\AstVisitor;
use Riverwaysoft\DtoConverter\ClassFilter\ClassFilterInterface;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * It converts PHP code string into a normalized DTO list suitable for converting into other languages
 */
class Normalizer
{
    public function __construct(private Parser $parser, private ?ClassFilterInterface $classFilter = null)
    {
    }

    public static function factory(?ClassFilterInterface $classFilter = null): self
    {
        return new self((new ParserFactory())->create(ParserFactory::PREFER_PHP7), $classFilter);
    }

    public function normalize(string $code): DtoList
    {
        $ast = $this->parser->parse($code);

        $dtoList = new DtoList();
        $visitor = new AstVisitor($dtoList, $this->classFilter);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $dtoList;
    }
}
