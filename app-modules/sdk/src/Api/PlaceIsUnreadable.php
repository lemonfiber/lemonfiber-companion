<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `watched` envelope did not hold what the contract says it holds.
 *
 * {@see GrantIsUnreadable}'s refusal, for the place a stack says it kept. A
 * developer reads it, so it is `sprintf` and never translated.
 */
final class PlaceIsUnreadable extends InvalidArgumentException
{
    /** A field arrived missing, or as another shape. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('The watched payload carried no readable `%s`.', $field->value));
    }
}
