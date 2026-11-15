<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `stack` envelope did not hold what the contract says it holds.
 *
 * Refused rather than drawn as something else: the operator's own stack drawn
 * as the bundled one, or words that would not bring it up drawn as words that
 * would, is worse than none. A developer reads it, so it is `sprintf` and
 * never translated.
 */
final class StackIsUnreadable extends InvalidArgumentException
{
    /** A field it needs is missing or not what the contract says. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The stack envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }
}
