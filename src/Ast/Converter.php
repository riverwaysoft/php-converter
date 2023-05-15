<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Ast;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Riverwaysoft\DtoConverter\ClassFilter\ClassFilterInterface;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpoint;
use Riverwaysoft\DtoConverter\Dto\ApiClient\ApiEndpointList;
use Riverwaysoft\DtoConverter\Dto\DtoList;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeFactory;

/**
 * It converts PHP code string into a normalized DTO list suitable for converting into other languages
 */
class Converter
{
    private Parser $parser;
    private PhpDocTypeParser $phpDocTypeParser;
    private PhpTypeFactory $phpTypeFactory;

    public function __construct(
        private ?ClassFilterInterface $dtoClassFilter = null,
    ) {
        $this->phpTypeFactory = new PhpTypeFactory();
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->phpDocTypeParser = new PhpDocTypeParser($this->phpTypeFactory);
    }

    /** @param string[]|iterable $listings */
    public function convert(iterable $listings): ConverterResult
    {
        $dtoList = new DtoList();
        $apiEndpointList = new ApiEndpointList();

        foreach ($listings as $listing) {
            $this->normalize($listing, $dtoList, $apiEndpointList);
        }

        return new ConverterResult(
            dtoList: $dtoList,
            apiEndpointList: $apiEndpointList,
        );
    }

    private function normalize(string $code, DtoList $dtoList, ApiEndpointList $apiEndpointList): void
    {
        $ast = $this->parser->parse($code);
        $dtoVisitor = new DtoVisitor($dtoList, $this->phpDocTypeParser, $this->phpTypeFactory, $this->dtoClassFilter);
        $symfonyControllerVisitor = new SymfonyControllerVisitor('DtoEndpoint', $apiEndpointList, $this->phpTypeFactory);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($dtoVisitor);
        $traverser->addVisitor($symfonyControllerVisitor);
        $traverser->traverse($ast);
    }
}
