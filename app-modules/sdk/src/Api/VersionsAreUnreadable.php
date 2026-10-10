<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `version` envelope arrived that cannot be read as the versions a stack runs.
 *
 * One kind for everything the reading refuses, so the adapter catches one and
 * the screen says the answer could not be read rather than drawing half of it.
 */
final class VersionsAreUnreadable extends InvalidArgumentException
{
    /** A field it owes was absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('What the stack runs arrived without a readable `%s`.', $field->value));
    }

    /** A group of the running release's notes could not be read. */
    public static function group(int $position): self
    {
        return new self(sprintf('Group %d of the running release\'s notes could not be read.', $position + 1));
    }
}
