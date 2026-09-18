<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The pairing material was good, and it is too old to use.
 *
 * A separate refusal from {@see PairingIsNotReadable} because it is a different
 * sentence to the person holding the phone. "That is not a pairing code" sends
 * somebody to check what they scanned; "that code has expired, ask the stack
 * for another" sends them back to the machine. Telling the first to somebody
 * who did everything right is how an operator learns that this screen is
 * unreliable.
 *
 * Material expires for a reason. Pairing material names a machine and
 * the certificate it will present, and a photograph of a QR code in somebody's
 * camera roll is a durable instruction to trust a host — one that outlives the
 * evening it was useful for, and that whoever picks up the phone later can act
 * on.
 */
final class PairingIsSpent extends InvalidArgumentException
{
    public static function since(Instant $expired, HowItWasRead $how): self
    {
        return new self(sprintf(
            'The pairing material read by %s expired at %d and cannot be used; the stack can produce more.',
            $how->value,
            $expired->epochSeconds(),
        ));
    }
}
