<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A release, or what a stack runs, arrived without a name it owes.
 *
 * Its own exception rather than a generic one, for the reason every refusal
 * here has its own: what an operator is told depends on which half of the
 * conversation went wrong, and a payload short of a field is the stack's half.
 */
final class VersionIsBlank extends InvalidArgumentException
{
    public static function inARelease(): self
    {
        return new self('A release arrived with no version to call it by.');
    }

    /** What a stack says it runs arrived with one of its versions blank, named. */
    public static function ofWhatRuns(string $which): self
    {
        return new self(sprintf('What the stack runs arrived with its `%s` version blank.', $which));
    }

    /** A group of changes in a release's notes arrived with no title. */
    public static function inAGroupOfChanges(): self
    {
        return new self('A group of changes in the release notes arrived with no title.');
    }
}
