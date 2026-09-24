<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * An upkeep reading arrived in a shape this side cannot render.
 *
 * Its own refusal rather than a shared one, for the reason every reader here
 * has its own: what an operator is told depends on which half of the
 * conversation went wrong, and a payload short of a field is the stack's half.
 */
final class UpkeepIsUnreadable extends InvalidArgumentException
{
    public static function missing(WireField $field): self
    {
        return new self(sprintf('An update reading arrived without its `%s`.', $field->value));
    }

    public static function state(string $said): self
    {
        return new self(sprintf('An update reading called the services `%s`, which is not a state.', $said));
    }

    /** One of the changes an update would make, named by where it sat. */
    public static function change(int $position): self
    {
        return new self(sprintf('Change %d in the update could not be read.', $position + 1));
    }

    /**
     * One service's share of an applied update, named by where it sat.
     *
     * One refusal for the row rather than one per field. Which of the three a
     * row was short of is not a different evening for the operator — the update
     * is reported and this side cannot say what became of that service — and
     * three refusals would be three sentences saying so.
     */
    public static function applied(int $position): self
    {
        return new self(sprintf('Service %d in the applied update could not be read.', $position + 1));
    }
}
