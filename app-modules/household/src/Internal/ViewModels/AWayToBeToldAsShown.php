<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/** One way to be told, as a member chooses it: its name as the household reads it, what choosing it sends, and whether they are told on it now. */
final readonly class AWayToBeToldAsShown
{
    public function __construct(
        public string $name,
        public string $id,
        public bool $receiving,
    ) {}
}
