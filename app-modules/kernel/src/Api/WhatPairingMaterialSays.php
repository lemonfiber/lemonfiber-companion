<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Everything pairing material is allowed to say, and its only spelling.
 *
 * A closed set, so an enum — `D4`. It was a `private const array` of three
 * string literals, and the literals then appeared a second time at each place
 * that read one: the list said `'expires'` and the reader asked for `'expires'`
 * independently. Rename a key in the list and the reader still asks for the old
 * one, so every payload is refused for carrying a field the format defines.
 * Nothing names the line that moved.
 *
 * **Closed rather than a list of forbidden names, and that is `N1-R48`.** The
 * requirement is that material must not carry a credential. Enforcing it by
 * refusing `credential`, `token`, `password` is wrong the first time somebody
 * picks a name nobody thought of, and it fails *open*: the app pairs happily,
 * having been handed a secret out of band. Three cases are defined and a fourth
 * key is refused whatever it is called, which needs no list to stay current.
 *
 * A stack with something new to say says it by raising the {@see WireVersion},
 * which produces a refusal naming the real problem rather than an app quietly
 * reading a payload it does not understand.
 */
enum WhatPairingMaterialSays: string
{
    /** Where the app should reach the stack. */
    case Address = 'address';

    /** The certificate that address will present. */
    case Fingerprint = 'fingerprint';

    /** When the invitation stops being one. */
    case Expires = 'expires';
}
