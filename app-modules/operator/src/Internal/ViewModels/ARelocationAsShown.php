<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where a copy's data was, and where it goes instead, as the stack wrote both. */
final readonly class ARelocationAsShown
{
    /**
     * @param string $was the data root the copy was taken against
     * @param string $now the one it goes to on this machine
     */
    public function __construct(
        public string $was,
        public string $now,
    ) {}
}
