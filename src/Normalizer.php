<?php

declare(strict_types=1);

namespace App;

use App\Ast\AstVisitor;
use App\Dto\DtoList;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * It converts PHP code string into a normalized DTO list suitable for converting into other languages
 */
class Normalizer
{
    public function __construct(private Parser $parser)
    {
    }

    public static function factory(): self
    {
        return new self((new ParserFactory())->create(ParserFactory::PREFER_PHP7));
    }

    public function normalize(string $code): DtoList
    {
        $traverser = new NodeTraverser();
        $dtoList = new DtoList();
        $visitor = new AstVisitor($dtoList);
        $traverser->addVisitor($visitor);
        $ast = $this->parser->parse($code);
        $traverser->traverse($ast);

        return $dtoList;
    }
}
