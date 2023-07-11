<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\OutputDiffCalculator;

use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Renderer\RendererConstant;

class OutputDiffCalculator
{
    public function __construct(
        private int $context = 3,
        private int $cliColorization = RendererConstant::CLI_COLOR_AUTO,
    ) {
    }

    public function calculate(string $oldFileContent, string $newFileContent): string
    {
        $diffOptions = [
            // https://github.com/jfcherng/php-diff#example
            'context' => $this->context,
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
            'cliColorization' => $this->cliColorization,
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
