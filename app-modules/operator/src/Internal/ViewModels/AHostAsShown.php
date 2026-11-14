<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where a hosted back-end is hosted, and what is sent to use it, word for word; nothing where it is not hosted. */
final readonly class AHostAsShown
{
    /** @param list<string> $leaves */
    public function __construct(
        public string $host,
        public array $leaves,
    ) {}
}
