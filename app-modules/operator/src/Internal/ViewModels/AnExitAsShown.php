<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * How a failed run ended, as the key that says it and the code it carries.
 */
final readonly class AnExitAsShown
{
    public function __construct(public string $said, public int $code) {}
}
