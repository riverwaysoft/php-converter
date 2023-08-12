<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * It converts PHP code string into a normalized DTO list suitable for converting into other languages
 */
class Converter
{
    private Parser $parser;

    public function __construct(
        /** @var ConverterVisitor[] */
        private array $visitors,
    ) {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

    /** @param string[]|iterable $listings */
    public function convert(iterable $listings): ConverterResult
    {
        $converterResult = new ConverterResult();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());
        foreach ($this->visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }

        foreach ($listings as $listing) {
            $ast = $this->parser->parse($listing);
            $traverser->traverse($ast);
        }

        foreach ($this->visitors as $visitor) {
            $converterResult->merge($visitor->getResult());
        }

        return $converterResult;
    }
}
