<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\Converter\PhpDocTypeParser;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeFactory;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\DtoConverter\Dto\PhpType\PhpUnknownType;

class PhpDocTypeParserTest extends TestCase
{
    /** @dataProvider getData */
    public function testBasicScenario(string $explanation, string $input, PhpTypeInterface|null $expected): void
    {
        $parser = new PhpDocTypeParser(new PhpTypeFactory());
        $result = $parser->parse($input);
        $this->assertEquals($result, $expected, sprintf("Assert failed. Data row: '%s'", $explanation));
    }

    /** @return array{string, string, PhpTypeInterface|null}[] */
    public function getData(): array
    {
        return [
            [
                'array of objects',
                '/** @var Recipe[] */',
                new PhpListType(
                    new PhpUnknownType('Recipe'),
                ),
            ],
            [
                'string only',
                '/** @var int */',
                PhpBaseType::int(),
            ],
            [
                'nullable string',
                '/** @var int|string */',
                new PhpUnionType([
                    PhpBaseType::int(),
                    PhpBaseType::string(),
                ]),
            ],
            [
                'nullable number',
                '/** @var int|null */',
                new PhpUnionType([
                    PhpBaseType::int(),
                    PhpBaseType::null(),
                ]),
            ],
            [
                '2d array',
                '/** @var int[][] */',
                new PhpListType(
                    new PhpListType(
                        PhpBaseType::int(),
                    ),
                ),
            ],
            [
                '3d array',
                '/** @var int[][][] */',
                new PhpListType(
                    new PhpListType(
                        new PhpListType(
                            PhpBaseType::int(),
                        ),
                    ),
                ),
            ],
            [
                'nullable array',
                '/** @var int[]|null */',
                PhpUnionType::nullable(
                    new PhpListType(
                        PhpBaseType::int(),
                    ),
                ),
            ],
            [
                '@var is required',
                '/** int[]|null */',
                null,
            ],
            [
                'type with any other decorator',
                '/**
                  *  @SomeDecorator
                  *  @var int
                 */',
                PhpBaseType::int(),
            ],
        ];
    }
}
