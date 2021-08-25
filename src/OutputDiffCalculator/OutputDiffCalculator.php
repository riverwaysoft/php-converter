<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\OutputDiffCalculator;

use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Renderer\RendererConstant;

class OutputDiffCalculator
{
    public function calculate(string $oldFileContent, string $newFileContent): string
    {
        $diffOptions = [
            'context' => 3,
            'ignoreCase' => false,
            'ignoreWhitespace' => false,
        ];

        $rendererOptions = [
            'detailLevel' => 'line',
            'language' => 'eng',
            'lineNumbers' => true,
            'separateBlock' => true,
            'showHeader' => true,
            'spacesToNbsp' => false,
            'tabSize' => 2,
            'mergeThreshold' => 0.8,
            'cliColorization' => RendererConstant::CLI_COLOR_AUTO,
            'outputTagAsString' => false,
            'jsonEncodeFlags' => \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
            'wordGlues' => [' ', '-'],
            'resultForIdenticals' => null,
        ];

        return DiffHelper::calculate(
            $oldFileContent,
            $newFileContent,
            'Unified',
            $diffOptions,
            $rendererOptions
        );
    }
}