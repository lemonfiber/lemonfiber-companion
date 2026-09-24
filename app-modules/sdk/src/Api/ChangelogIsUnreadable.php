<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `changelog` block arrived in a shape this side cannot render.
 *
 * Its own refusal rather than one per envelope, because the block has one shape
 * wherever it arrives and one reader, {@see \Modules\Sdk\Internal\Changelogs}.
 * Every adapter that hands a payload to that reader catches this beside its own
 * refusal, and turns both into the same obstacle.
 */
final class ChangelogIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('A changelog arrived without its `%s`.', $field->value));
    }

    /** A release the record listed, named by where it sat rather than by a name it lacks. */
    public static function release(int $position): self
    {
        return new self(sprintf('Release %d in the changelog could not be read.', $position + 1));
    }

    /** The release that is running, where the stack named one this side cannot read. */
    public static function running(): self
    {
        return new self('The running release in the changelog could not be read.');
    }
}
