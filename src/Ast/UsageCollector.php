<?php

declare(strict_types=1);

namespace Riverwaysoft\PhpConverter\Ast;

use Webmozart\Assert\Assert;

class UsageCollector
{
    private int|null $startMemory = null;
    private int|null $endMemory = null;
    private float|null $startTime = null;
    private float|null $endTime = null;

    public function startMeasuring(): void
    {
        $this->startMemory = memory_get_peak_usage();
        $this->startTime = microtime(true);
    }

    public function endMeasuring(): void
    {
        $this->endMemory = memory_get_peak_usage();
        $this->endTime = microtime(true);
    }

    /** @return array{
     *     memory: array{
     *       start: string,
     *       end: string,
     *       peak: string
     *    },
     *    time: array{seconds: float}
     * }
     */
    public function report(): array
    {
        Assert::notNull($this->startMemory);
        Assert::notNull($this->endMemory);
        Assert::notNull($this->startTime);
        Assert::notNull($this->endTime);

        return [
            'memory' => [
                'start' => ($this->startMemory / 1024 / 1024) . " MB\n",
                'end' => ($this->endMemory / 1024 / 1024) . " MB\n",
                'peak' => (($this->endMemory - $this->startMemory) / 1024 / 1024) . " MB\n",
            ],
            'time' => [
                'seconds' => $this->endTime - $this->startTime,
            ]
        ];
    }
}
