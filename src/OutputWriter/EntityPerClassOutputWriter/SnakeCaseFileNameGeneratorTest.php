<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\EntityPerClassOutputWriter;

use PHPUnit\Framework\TestCase;

class SnakeCaseFileNameGeneratorTest extends TestCase
{
    public function testSnakeCase(): void
    {
        $generator = new SnakeCaseFileNameGenerator('.ts');
        $result = $generator->generateFileNameWithExtension('TeamMember');
        $this->assertEquals('team_member.ts', $result);
    }

    public function testWrongExtension(): void
    {
        $this->expectExceptionMessageMatches('/^Invalid file extension: /');
        $generator = new SnakeCaseFileNameGenerator('ts');
    }
}
