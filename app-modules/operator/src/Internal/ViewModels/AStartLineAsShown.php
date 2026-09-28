<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The stack's own line for what a running start waits on, as a screen draws it.
 *
 * Empty where nothing has been said yet, which is what the screen reads to
 * draw its own sentence instead.
 */
final readonly class AStartLineAsShown
{
    public function __construct(public string $said) {}
}
