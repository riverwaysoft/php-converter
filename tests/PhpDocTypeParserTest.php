<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Riverwaysoft\DtoConverter\DocBlockTypeParser\PhpDocDockTypeParser;
use Riverwaysoft\DtoConverter\Dto\ListType;
use Riverwaysoft\DtoConverter\Dto\SingleType;

class PhpDocTypeParserTest extends TestCase
{
    public function testBasicScenario()
    {
        $parser = new PhpDocDockTypeParser();
        $result = $parser->parse('/** @var Recipe[] */');

        $this->assertEquals($result, new ListType(new SingleType('Recipe')));
    }

}