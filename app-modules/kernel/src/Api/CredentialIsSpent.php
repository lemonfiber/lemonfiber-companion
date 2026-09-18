<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * Somebody asked a credential for its value a second time.
 *
 * The credential is exchanged for a session **once** and is not
 * retained for re-sending, and this is what makes "once" a fact rather than an
 * intention. The second read is refused rather than answered, because the
 * alternative — answering with the value again — is precisely the retention the
 * requirement forbids, and it would be invisible.
 *
 * Raised rather than answered with, and for the usual reason: there is nothing a
 * caller can do with this except stop. A caller holding a spent credential has
 * already exchanged it, so what it wants is the session it was given, and the
 * place that has the session is the place that did the exchange.
 *
 * `InvalidArgumentException` like every other refusal here — see
 * {@see PairingIsNotReadable} for why the runtime kind would make this the one
 * behaviour no test could exercise.
 */
final class CredentialIsSpent extends InvalidArgumentException
{
    /** Asked for a second time. */
    public static function already(): self
    {
        // One literal rather than three concatenated. A concatenated message is
        // several mutants — remove a side, swap two — and none of them changes
        // what any test asserts, because a test matching a fragment matches the
        // mutated string too. Kept on one line for that reason.
        return new self('This credential has already been exchanged for a session. N1-R7 keeps nothing to re-send: ask the exchange for the session it returned rather than the credential for its value again.');
    }
}
