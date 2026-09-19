<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing the stack is set to, as it is safe to show.
 *
 * A name and what it holds, and nothing else. The wire carries a third field —
 * `secret` — which is not here because it is not a property of the setting so
 * much as a statement about the value, and {@see WhatASettingHolds} is where
 * that statement lives in a form a screen cannot read backwards.
 *
 * **No kind, no group, no default.** The contract's row carries none of them,
 * and a type with fields the wire cannot fill is a type whose empty half every
 * reader has to remember is always empty.
 */
final readonly class Setting
{
    private function __construct(public string $key, public WhatASettingHolds $holds) {}

    /**
     * A setting the stack named.
     *
     * The name is trimmed and then required, in that order: a key of spaces is
     * a key the operator cannot read, cannot search for and cannot later change
     * — and a listing drawn from one would have a row nothing identifies.
     */
    public static function called(string $key, WhatASettingHolds $holds): self
    {
        $named = trim($key);

        if ($named === '') {
            throw SettingIsUnnamed::inTheListing();
        }

        return new self(key: $named, holds: $holds);
    }
}
