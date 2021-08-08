<?php

declare(strict_types=1);

namespace App;

use App\Annotation\PhpAttributesChecker;
use App\Annotation\PhpDocChecker;
use App\Ast\AstVisitor;
use App\Dto\DtoList;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class Normalizer
{
    public function normalize(string $code): DtoList
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $traverser = new NodeTraverser();
        $dtoList = new DtoList();
        $visitor = new AstVisitor($dtoList, [new PhpAttributesChecker(), new PhpDocChecker()]);
        $traverser->addVisitor($visitor);
        $ast = $parser->parse($code);
        $traverser->traverse($ast);

        return $dtoList;
    }
}
