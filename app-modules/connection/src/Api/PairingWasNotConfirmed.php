<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use RuntimeException;

/**
 * Typed pairing reached the point of becoming a stack without a confirmation.
 *
 * `N1-R50` is explicit that the app must not proceed on an unconfirmed
 * fingerprint, and this is the one path where that could happen: material read
 * by camera carried its own digest, and material read by a person did not —
 * somebody has to have compared it.
 *
 * Raised rather than answered with, which is this module's exception rather
 * than its rule. There is no half-paired stack to carry on with, so there is
 * nothing for a caller to do with a value except stop — and an outcome type
 * here would make *proceed anyway* a branch that exists.
 */
final class PairingWasNotConfirmed extends RuntimeException
{
    public static function becauseItWasTyped(): self
    {
        return new self(
            'This pairing code was typed rather than scanned, so nothing has compared the '
            . 'certificate against what the stack is showing. N1-R50 does not allow pairing '
            . 'to complete on that.',
        );
    }

    public static function aboutAnotherCertificate(): self
    {
        return new self(
            'The fingerprint the operator confirmed is not the one this pairing material '
            . 'carries, so the confirmation belongs to another machine.',
        );
    }
}
