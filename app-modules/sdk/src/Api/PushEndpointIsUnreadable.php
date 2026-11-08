<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Lemonfiber\Sdk\Generated\Kind;

use function sprintf;

/**
 * The `push-endpoint` or `push-endpoints` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated. Refused rather
 * than salvaged: a registration the phone cannot confirm is one it does not
 * claim, and a removal it cannot confirm is one still owed.
 */
final class PushEndpointIsUnreadable extends InvalidArgumentException
{
    /** A field is absent, blank, or not what the contract says it is. */
    public static function missing(Kind $kind, NamesAWireField $field): self
    {
        return new self(sprintf('The %s envelope has no readable `%s`, so it did not come from a lemonfiber this app can read.', $kind->value, $field->value));
    }
}
