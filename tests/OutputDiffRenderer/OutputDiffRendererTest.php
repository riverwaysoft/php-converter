<?php

declare(strict_types=1);

namespace App\Tests\OutputDiffRenderer;

use Jfcherng\Diff\Renderer\RendererConstant;
use PHPUnit\Framework\TestCase;
use Riverwaysoft\PhpConverter\OutputDiffRenderer\OutputDiffRenderer;
use Spatie\Snapshots\MatchesSnapshots;

class OutputDiffRendererTest extends TestCase
{
    use MatchesSnapshots;

    public function testBasicDiff(): void
    {
        $diff = new OutputDiffRenderer(
            context: 3,
            // Remove terminal codes to have a clean readable test snapshot
            cliColorization: RendererConstant::CLI_COLOR_DISABLE,
        );

        $oldFile = <<<'CODE'
type User = {
  age: string;
  name: string;
}
CODE;

        $newFile = <<<'CODE'
type User = {
  age: number;
  name: string;
}

enum Role { Admin, User }
CODE;

        $result = $diff->calculate($oldFile, $newFile);
        $this->assertMatchesSnapshot($result);
    }
}
