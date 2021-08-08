<?php

declare(strict_types=1);

namespace App;

use App\Dto\DtoList;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class DtoGenerator
{
    public function generate(string $code): DtoList
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $traverser = new NodeTraverser;
        $dtoList = new DtoList();
        $visitor = new AstVisitor($dtoList);
        $traverser->addVisitor($visitor);
        $ast = $parser->parse($code);
        $traverser->traverse($ast);

        return $dtoList;
    }
}