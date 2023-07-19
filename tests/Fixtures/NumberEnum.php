<?php

declare(strict_types=1);

use MyCLabs\Enum\Enum;

final class NumberEnum extends Enum
{
    private const VIEW = 0;
    private const EDIT = 1;
    private const CREATE = 2;
}
