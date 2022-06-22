<?php

declare(strict_types=1);

namespace App\Tests;

use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\DocBlockTypeParser\PhpDocDockTypeParser;

class PhpDocTypeParserTest extends TestCase
{
    public function testBasicScenario()
    {
        $parser = new PhpDocDockTypeParser();
        $result = $parser->parse('/** @param Foo $foo */');

        $this->assertEquals($result, new PhpDocNode([
            new PhpDocTagNode(
                '@param',
                new ParamTagValueNode(
                    new IdentifierTypeNode('Foo'),
                    false,
                    '$foo',
                    ''
                )
            ),
        ]));
    }

}