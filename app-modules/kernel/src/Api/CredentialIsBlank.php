<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * There is nothing here to exchange for a session.
 *
 * Its own type rather than {@see SessionIsBlank}, because the two describe
 * opposite ends of the same exchange and a reader wants to know which. A blank
 * session is a stack that admitted this app and sent nothing back; a blank
 * credential is material that never carried one — the first is a server fault
 * and the second is a pairing that did not produce what it should have.
 *
 * The message says nothing about what arrived, for the reason `SessionIsBlank`
 * gives: an exception carrying a credential puts it in a stack trace, and a
 * stack trace is exactly what ends up in a diagnostic report (`N1-R15`).
 */
final class CredentialIsBlank extends InvalidArgumentException
{
    public static function inPairingMaterial(): self
    {
        return new self('The pairing material carried an empty credential, which nothing can be exchanged for.');
    }
}
