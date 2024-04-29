<?php

declare(strict_types=1);

namespace App\Tests\Ast;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\Ast\PhpDocTypeParser;
use Riverwaysoft\PhpConverter\Dto\DtoClassProperty;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpBaseType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpListType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpOptionalType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpTypeInterface;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnionType;
use Riverwaysoft\PhpConverter\Dto\PhpType\PhpUnknownType;
use function sprintf;

class PhpDocTypeParserTest extends TestCase
{
    #[DataProvider('getDataVarAndReturn')]
    public function testBasicScenario(string $explanation, string $input, PhpTypeInterface|null $expected): void
    {
        $parser = new PhpDocTypeParser();
        $result = $parser->parseVarOrReturn($input);
        $this->assertEquals($result, $expected, sprintf("Assert failed. Data row: '%s'", $explanation));
    }

    /**
     * @param PhpTypeInterface[] $expected
     */
    #[DataProvider('getDataParamTags')]
    public function testMethodParams(string $explanation, string $input, array $expected): void
    {
        $parser = new PhpDocTypeParser();
        $result = $parser->parseMethodParams($input);
        $this->assertEquals($result, $expected, sprintf("Assert failed. Data row: '%s'", $explanation));
    }

    public function testClassComments(): void
    {
        $parser = new PhpDocTypeParser();
        $input = "/**
         * @template K The key type
         * @template V The input value type
         * @template V2 The output value type
         */";
        $result = $parser->parseClassComments($input);
        $this->assertEquals($result, [
            new PhpUnknownType('K'),
            new PhpUnknownType('V'),
            new PhpUnknownType('V2'),
        ]);
    }

    /** @return array{string, string, DtoClassProperty[]}[] */
    public static function getDataParamTags(): array
    {
        return [
            [
                'generic properties',
                "/**
    * @param T[] \$array 
    * @param T \$one 
    */",
                [
                    new DtoClassProperty(new PhpListType(new PhpUnknownType('T')), 'array'),
                    new DtoClassProperty(new PhpUnknownType('T'), 'one'),
                ],
            ],
            [
                'properties without variable name',
                "/** @param string test */",
                [],
            ],
            [
                'properties without variable name',
                "/** @param */",
                [],
            ],
        ];
    }

    /** @return array{string, string, PhpTypeInterface|null}[] */
    public static function getDataVarAndReturn(): array
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
                'nullable optional string array',
                '/** @var ?string[] */',
                new PhpOptionalType(
                    new PhpListType(
                        PhpBaseType::string(),
                    ),
                ),
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
                new PhpListType(
                    new PhpUnionType([
                        PhpBaseType::int(),
                        PhpBaseType::string(),
                    ]),
                ),
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
                'return generics',
                '/**
                  * @return JsonResponse<PaginatedResponse<User>>
                  */',
                new PhpUnknownType('JsonResponse', [
                    new PhpUnknownType('PaginatedResponse', [
                        new PhpUnknownType('User'),
                    ], []),
                ], []),
            ],
        ];
    }
}
