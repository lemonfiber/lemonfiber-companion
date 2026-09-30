<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

/** One setting as it is written down: a count, or a word. */
final readonly class WrittenAs
{
    public function __construct(public int|string $value) {}
}
