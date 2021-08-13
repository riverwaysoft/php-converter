<?php

declare(strict_types=1);

namespace App\Testing;

use Spatie\Snapshots\Drivers\TextDriver;

class TypeScriptSnapshotComparator extends TextDriver
{
    public function extension(): string
    {
        return 'ts';
    }
}
