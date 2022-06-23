<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\AstParser\PhpDocTypeParser;
use Riverwaysoft\DtoConverter\Dto\ListType;
use Riverwaysoft\DtoConverter\Dto\SingleType;
use Riverwaysoft\DtoConverter\Dto\UnionType;

class PhpDocTypeParserTest extends TestCase
{
    /** @dataProvider getData */
    public function testBasicScenario(string $explanation, string $input, ListType|UnionType|SingleType|null $expected)
    {
        $parser = new PhpDocTypeParser();
        $result = $parser->parse($input);
        $this->assertEquals($result, $expected, sprintf("Assert failed. Data row: %s", $explanation));
    }

    public function getData()
    {
        return [
            [
                'array of objects',
                '/** @var Recipe[] */',
                new ListType(
                    new SingleType('Recipe'),
                ),
            ],
            [
                'string only',
                '/** @var number */',
                new SingleType('number'),
            ],
            [
                'nullable string',
                '/** @var number|string */',
                new UnionType([
                    new SingleType('number'),
                    new SingleType('string'),
                ]),
            ],
            [
                'nullable number',
                '/** @var number|null */',
                new UnionType([
                    new SingleType('number'),
                    SingleType::null(),
                ]),
            ],
            [
                '2d array',
                '/** @var number[][] */',
                new ListType(
                    new ListType(
                        new SingleType('number'),
                    ),
                ),
            ],
            [
                '3d array',
                '/** @var number[][][] */',
                new ListType(
                    new ListType(
                        new ListType(
                            new SingleType('number'),
                        ),
                    ),
                ),
            ],
            [
                'nullable array',
                '/** @var number[]|null */',
                UnionType::nullable(
                    new ListType(
                        new SingleType('number'),
                    ),
                ),
            ],
            [
                '@var is required',
                '/** number[]|null */',
                null,
            ],
        ];
    }
}