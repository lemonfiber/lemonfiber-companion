<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `held` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see HouseholdIsUnreadable} is, for the payload a member's
 * shelf is read from, and for its reason: every one of these is a bug
 * somewhere other than here, and the message names the field because that is
 * the only thing that shortens the search. A developer reads it, so it is
 * `sprintf` and never translated.
 *
 * **A shelf is worth refusing rather than salvaging.** A shelf one holding
 * short reads as a library that does not have the thing somebody is looking
 * for, and they will go and ask for it again — which is a request made about
 * something the house already owns.
 */
final class ShelfIsUnreadable extends InvalidArgumentException
{
    /** A field the contract requires did not arrive, or arrived as another shape. */
    public static function missing(WireField $field): self
    {
        return new self(sprintf('The held payload carried no readable `%s`.', $field->value));
    }

    /** One row of the shelf could not be read, named by where it sat. */
    public static function holding(int $position): self
    {
        return new self(sprintf('The holding at position %d could not be read.', $position));
    }

    /** A medium arrived that this build does not recognise. */
    public static function medium(string $said): self
    {
        return new self(sprintf('`%s` is not a medium this build knows.', $said));
    }
}
