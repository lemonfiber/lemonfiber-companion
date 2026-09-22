<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One thing the stack is set to, as it is safe to show.
 *
 * A name, what it holds, and where it came from. The wire carries `secret`
 * too, which is not here because it is not a property of the setting so much
 * as a statement about the value, and {@see WhatASettingHolds} is where that
 * statement lives in a form a screen cannot read backwards.
 *
 * **The origin is a field of the setting rather than a statement about it**,
 * which is why it sits here beside the key while `secret` does not. Who set a
 * value is a fact about the value; whether it is safe to print is a decision
 * about showing it. The first is required to be shown wherever the setting is,
 * and a type that carried it anywhere else would let a screen draw the row
 * without it.
 *
 * **No kind, no group, no default.** The contract's row carries none of them,
 * and a type with fields the wire cannot fill is a type whose empty half every
 * reader has to remember is always empty.
 */
final readonly class Setting
{
    private function __construct(
        public string $key,
        public WhatASettingHolds $holds,
        public WhereASettingCameFrom $from,
    ) {}

    /**
     * A setting the stack named.
     *
     * The name is trimmed and then required, in that order: a key of spaces is
     * a key the operator cannot read, cannot search for and cannot later change
     * — and a listing drawn from one would have a row nothing identifies.
     */
    public static function called(
        string $key,
        WhatASettingHolds $holds,
        WhereASettingCameFrom $from,
    ): self {
        $named = trim($key);

        if ($named === '') {
            throw SettingIsUnnamed::inTheListing();
        }

        return new self(key: $named, holds: $holds, from: $from);
    }
}
