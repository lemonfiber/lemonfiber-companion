<?php

declare(strict_types=1);

namespace Tests\Support;

use function app;

use Illuminate\Contracts\Translation\Translator;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\TheWayAround;

/**
 * The way around a test's stacks, made as the container makes it.
 *
 * Every screen about a stack is handed one, so a test that builds a screen by
 * hand takes it from here, and what it is made of is written once.
 */
final readonly class AroundThePhone
{
    public static function holding(Stacks $stacks): TheWayAround
    {
        return new TheWayAround($stacks, app(Translator::class));
    }
}
