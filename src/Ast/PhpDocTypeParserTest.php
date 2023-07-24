<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use function sprintf;

class PhpDocTypeParserTest extends TestCase
{
    #[DataProvider('getData')]
    public function testBasicScenario(string $explanation, string $input, PhpTypeInterface|null $expected): void
    {
        $parser = new PhpDocTypeParser();
        $result = $parser->parse($input);
        $this->assertEquals($result, $expected, sprintf("Assert failed. Data row: '%s'", $explanation));
    }

    /** @return array{string, string, PhpTypeInterface|null}[] */
    public static function getData(): array
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
                'array with union inside',
                '/** @var (int|string)[] */',
                new PhpListType(new PhpUnionType([
                    PhpBaseType::int(),
                    PhpBaseType::string(),
                ]), ),
            ],
            [
                '@var is required',
                '/** int[]|null */',
                null,
            ],
            [
                'array shapes are not yet supported',
                '/** @var array{"foo": int, "bar": string} */',
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
            [
                'generics',
                '/**
                  * @return JsonResponse<PaginatedResponse<User>>
                  */',
                new PhpUnknownType('JsonResponse', [], [
                    new PhpUnknownType('PaginatedResponse', [], [
                        new PhpUnknownType('User'),
                    ]),
                ]),
            ],
        ];
    }
}
