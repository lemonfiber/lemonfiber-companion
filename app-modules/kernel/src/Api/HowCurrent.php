<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether the stack is up to date, as the stack answered it.
 *
 * Three states rather than a version to compare, because comparing is what
 * That is refused: the app holds no opinion about which of two version
 * strings is later, and one that formed one would be wrong about a withdrawn
 * release, a patch series and a stack whose channel the operator changed.
 *
 * *Stale* is apart from *pending* and that is the whole reason this is three
 * cases. Pending is an update waiting to be taken; stale is a stack that has
 * not looked recently enough to know. Told apart, an operator can act on the
 * first and refresh on the second. Flattened into *not current*, both become a
 * red dot that means nothing in particular.
 */
enum HowCurrent: string
{
    /** Nothing is waiting: what is running is what the channel offers. */
    case Current = 'current';
    /** An update is waiting to be taken. */
    case Pending = 'pending';
    /** The stack has not looked recently enough to say. */
    case Stale = 'stale';

    /**
     * What a screen says this is.
     *
     * The catalogue key is built from the case rather than listed against it,
     * so a case added without a line fails the rule that reads both.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('updates.how.%s', $this->value);
    }

    /**
     * Whether there is an update here to be taken.
     *
     * The question every caller actually asks, answered once. It is refused
     * to offer applying one where the stack said current, and a screen deciding
     * that for itself would be a second place this is known.
     */
    public function hasSomethingWaiting(): bool
    {
        return $this === self::Pending;
    }
}
