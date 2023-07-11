<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputWriter\EntityPerClassOutputWriter;

use PHPUnit\Framework\TestCase;

class KebabCaseFileNameGeneratorTest extends TestCase
{
    public function testKebabCase(): void
    {
        $generator = new KebabCaseFileNameGenerator('.ts');
        $result = $generator->generateFileNameWithExtension('TeamMember');
        $this->assertEquals('team-member.ts', $result);
    }

    public function testWrongExtension(): void
    {
        $this->expectExceptionMessageMatches('/^Invalid file extension: /');
        $generator = new KebabCaseFileNameGenerator('ts');
    }
}
