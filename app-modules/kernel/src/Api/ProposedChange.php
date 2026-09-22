<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One setting, what it would hold, what it holds now, and what that costs.
 *
 * **A difference rather than a value, and `from` is what makes it one.** An
 * operator deciding whether to go ahead is deciding between two things, and a
 * screen showing only the new one would be asking them to remember the old one
 * correctly.
 *
 * One setting, because one call changes one setting.
 */
final readonly class ProposedChange
{
    private function __construct(
        public string $key,
        public string $to,
        public WhatItHoldsNow $from,
        public Cost $cost,
    ) {}

    /**
     * A change the stack described.
     *
     * The key is trimmed and then required, as a setting's is: a change with
     * no name is one an operator cannot be asked about and one nothing could
     * apply afterwards.
     */
    public static function of(string $key, string $to, WhatItHoldsNow $from, Cost $cost): self
    {
        $named = trim($key);

        if ($named === '') {
            throw SettingIsUnnamed::inTheListing();
        }

        return new self(key: $named, to: $to, from: $from, cost: $cost);
    }
}
