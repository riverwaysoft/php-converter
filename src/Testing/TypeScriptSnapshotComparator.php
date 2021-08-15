<?php

declare(strict_types=1);

namespace Riverwaysoft\DtoConverter\Testing;

use Spatie\Snapshots\Drivers\TextDriver;

class TypeScriptSnapshotComparator extends TextDriver
{
    public function extension(): string
    {
        return 'ts';
    }
}
