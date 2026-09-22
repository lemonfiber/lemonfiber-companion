<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A setting and what to put in it.
 *
 * One type rather than two arguments, because one call changes one setting and
 * the pair never travels apart. A port taking `string $key, string $value`
 * takes two things of the same type in an order nothing enforces, and
 * transposing them writes the key into the value — which on a credential
 * setting writes a setting name where a password belongs and reads afterwards
 * as an operator's own mistake.
 *
 * **The key is required and the value is not.** A blank key names nothing;
 * a blank value is a setting being cleared, which is a thing an operator
 * legitimately does and a thing a type refusing empty strings would make
 * impossible.
 */
final readonly class WhatToSet
{
    private function __construct(public string $key, public string $value) {}

    public static function to(string $key, string $value): self
    {
        $named = trim($key);

        if ($named === '') {
            throw SettingIsUnnamed::inTheListing();
        }

        // The value is not trimmed. A setting whose value has a trailing space
        // is a setting the operator typed that way, and an app quietly
        // correcting it would be deciding something about a value it does not
        // understand — a path, a token, a delimiter.
        return new self(key: $named, value: $value);
    }
}
