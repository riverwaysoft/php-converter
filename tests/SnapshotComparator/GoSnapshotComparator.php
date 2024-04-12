<?php

declare(strict_types=1);

namespace App\Tests\SnapshotComparator;

use Spatie\Snapshots\Drivers\TextDriver;

class GoSnapshotComparator extends TextDriver
{
    public function extension(): string
    {
        return 'go';
    }
}
