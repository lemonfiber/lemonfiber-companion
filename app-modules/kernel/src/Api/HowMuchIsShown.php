<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether a listing is the whole of what there is.
 *
 * The `stuck` envelope carries `incomplete`, and a list rendered without it is
 * a screen quietly claiming to be complete. That claim is the one an operator
 * acts on: somebody reading four stalled titles decides the fifth they were
 * expecting arrived fine, and closes the app.
 *
 * **An enum rather than the wire's boolean**, for {@see Undoing}'s reason. A
 * bare `true` at a call site says nothing about which way round it goes (`D5`)
 * — and `incomplete: false` read as *complete* is a negation an eye skips —
 * and a boolean has no word an operator can read (`L2`). These two cases have
 * one each.
 *
 * **Named for what is shown rather than for what is missing.** The contract
 * says `incomplete` because a contract describes a payload; a screen says how
 * much of it you are looking at, and *some of it* is a sentence somebody can
 * act on where *incomplete* is a field name.
 */
enum HowMuchIsShown: string
{
    /** Everything the stack had, so nothing is being kept back. */
    case AllOfIt = 'all-of-it';

    /** Part of it, and the stack said so. */
    case SomeOfIt = 'some-of-it';

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, the way every word in this app reaches the catalogue
     * — see {@see Conclusion::saidOnTheScreen()}. `AllOfIt` has a line of its
     * own rather than rendering as nothing, because a screen that says nothing
     * when a list is whole and something when it is not teaches an operator to
     * read silence, and silence is also what a screen that forgot the flag
     * produces.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.shown.%s', $this->value);
    }

    /** Whether what follows is all there is. */
    public function isTheWhole(): bool
    {
        return $this === self::AllOfIt;
    }
}
