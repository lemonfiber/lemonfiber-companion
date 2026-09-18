<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A stack offered repairs and did not name the listing they came in.
 *
 * Refused rather than carried, because of what the name is for: a confirmation
 * quotes it so the engine can tell whether the machine has moved since the
 * operator looked. Without it, the only yes this app could send is
 * the one with no listing attached — standing consent — and this
 * surface never sends that.
 *
 * So an unnamed offer is not a listing with a field missing. It is a listing
 * nobody can safely agree to, and the fault belongs where the payload is read
 * rather than on the screen at the moment somebody taps.
 */
final class OfferHasNoName extends InvalidArgumentException
{
    public static function fromTheStack(): self
    {
        return new self(
            'The stack offered repairs without naming the listing they came in, so nothing could be agreed to against it.',
        );
    }
}
