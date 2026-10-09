<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `part-way` envelope did not hold what the contract says it holds.
 *
 * {@see TitleIsUnreadable}'s refusal, for what a member was part-way through.
 * A developer reads it, so it is `sprintf` and never translated.
 */
final class PartWayIsUnreadable extends InvalidArgumentException
{
    /** A field arrived missing, or as another shape. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('The part-way payload carried no readable `%s`.', $field->value));
    }

    /** One item could not be read, named by where it sat. */
    public static function item(int $at): self
    {
        return new self(sprintf('Item %d in the part-way payload could not be read.', $at));
    }
}
