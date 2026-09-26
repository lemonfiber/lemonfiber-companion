<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A copy's `scope` did not hold what the contract says it holds.
 *
 * Refused rather than read as the whole stack, because a scope stated as
 * something it is not is a copy agreed to as something it is not.
 */
final class ScopeIsUnreadable extends InvalidArgumentException
{
    /** A field of the scope is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'A copy\'s scope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One tree of an existing setup's copy is not what the contract says it is. */
    public static function tree(int $position): self
    {
        return new self(sprintf(
            'Tree %d of a copy\'s scope is not readable. A copy of somebody else\'s setup is stated whole or not at all.',
            $position,
        ));
    }
}
