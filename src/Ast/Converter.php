<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Riverwaysoft\PhpConverter\ClassFilter\ClassFilterInterface;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\PhpConverter\Dto\ApiClient\ApiEndpointList;
use Riverwaysoft\PhpConverter\Dto\DtoList;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeFactory;

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

        foreach ($listings as $listing) {
            $ast = $this->parser->parse($listing);
            $traverser = new NodeTraverser();

            foreach ($this->visitors as $visitor) {
                $traverser->addVisitor($visitor);
            }

            $traverser->traverse($ast);

            foreach ($this->visitors as $visitor) {
                $converterResult->merge($visitor->popResult());
            }
        }

        return $converterResult;
    }
}
