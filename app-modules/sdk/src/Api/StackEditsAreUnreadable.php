<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A list of stack files the operator edited arrived in a shape this side cannot read.
 *
 * Its own refusal rather than one per envelope, because the list has one shape
 * wherever it arrives and one reader, {@see \Modules\Sdk\Internal\StackEditsSent}.
 * Every adapter that hands a list to that reader catches this beside its own
 * refusal, and turns both into the same obstacle.
 */
final class StackEditsAreUnreadable extends InvalidArgumentException
{
    /** The list itself was not there, or was not a list. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('The stack files the operator edited arrived without their `%s`.', $field->value));
    }

    /** One file in it, named by where it sat rather than by a name it lacks. */
    public static function entry(int $position): self
    {
        return new self(sprintf('Edited stack file %d could not be read.', $position + 1));
    }
}
