<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `version` envelope arrived that cannot be read as the versions a stack runs.
 *
 * One kind for everything the reading refuses, so the adapter catches one and
 * the screen says the stack did not answer rather than drawing half of it.
 */
final class VersionsAreUnreadable extends InvalidArgumentException
{
    /** A field it owes was absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('What the stack runs arrived without a readable `%s`.', $field->value));
    }

    /** Whether its notes describe it was a word the contract does not have. */
    public static function notes(string $said): self
    {
        return new self(sprintf('What the stack runs says its notes stand at `%s`, which is not a word the contract has.', $said));
    }

    /** A group of the running release's notes could not be read. */
    public static function group(int $position): self
    {
        return new self(sprintf('Group %d of the running release\'s notes could not be read.', $position + 1));
    }
}
