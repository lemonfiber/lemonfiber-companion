<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `pairing` envelope arrived without something a pairing code needs.
 *
 * Refused rather than shown: a code drawn from half an answer is one another
 * phone would scan and be refused by, with nothing on this screen to say why.
 */
final class PairingIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The pairing envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }
}
