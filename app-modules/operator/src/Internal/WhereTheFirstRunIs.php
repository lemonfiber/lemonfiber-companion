<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function count;
use function sprintf;

/**
 * Where somebody is in the first run, and how much of it is left.
 *
 * `N1-R35` already refuses an empty operator surface on a launch with no stack,
 * and one screen with two buttons on it satisfies that while leaving somebody
 * to work out what this application is and why pairing needs a machine they may
 * not be standing at. `N1-R54` makes it a sequence with a stated end.
 *
 * **An enum rather than an integer**, because the set is closed and a closed set
 * is a type (`D4`) — and because the step a screen is on is the sort of thing an
 * off-by-one turns into a blank screen nobody can name.
 *
 * It carries its own position: `N1-R55` asks every step to say which it is and
 * how many there are, and a step that had to be told its number would be a
 * number kept somewhere else and corrected when the sequence changed.
 */
enum WhereTheFirstRunIs: string
{
    /** What this application is, before it asks for anything. */
    case WhatThisIs = 'what_this_is';

    /**
     * That setup happens at the machine.
     *
     * `N1-R4` puts first-run setup on the host, so somebody who installed the
     * app first has arrived in the wrong place. They are told once, in a
     * sentence, rather than discovering it by pairing and finding nothing.
     */
    case AtTheMachine = 'at_the_machine';

    /** The pairing itself, which is where leaving early also lands. */
    case Pairing = 'pairing';

    /**
     * Which step this is, counting from one, as a person would say it.
     *
     * Walked rather than searched. `array_search` cannot fail here — a case is
     * always among its own cases — so the `false` arm it forces is a line no
     * test can reach and no mutation can be caught on, which the coverage floor
     * is right to refuse. A loop that breaks on itself has no such arm.
     */
    public function step(): int
    {
        $at = 0;

        foreach (self::cases() as $case) {
            $at++;

            if ($case === $this) {
                break;
            }
        }

        return $at;
    }

    /** How many there are, read off the cases rather than written down. */
    public function ofHowMany(): int
    {
        return count(self::cases());
    }

    /**
     * The step after this one, or this one where there is none.
     *
     * The last step answering itself rather than null: a sequence that ends by
     * handing back nothing makes every caller decide what nothing means, and
     * `Pairing` is where the sequence was going anyway.
     */
    public function andThen(): self
    {
        $cases = self::cases();

        // `step()` counts from one, so it is already the index of the next
        // case. Compared against the count rather than subscripted with a
        // default, which `C9` refuses for the right reason: a default reads as
        // *nobody knows whether the key is there*, and this type knows exactly
        // — it is the one that decides how many there are.
        $next = $this->step();

        return $next < count($cases) ? $cases[$next] : $this;
    }

    /** Whether this step is the pairing, which is what a screen draws last. */
    public function isThePairing(): bool
    {
        return $this === self::Pairing;
    }

    /**
     * The key a screen translates for what this step is about.
     *
     * Built from the case rather than written beside it, which is
     * {@see \Modules\Kernel\Api\Permission::reason()}'s shape and its
     * argument: a key spelled twice is a key that drifts, and the drift shows
     * up on the glass rather than in a run. The cost is that `L7` cannot see a
     * derived key, which is what `EveryDerivedKeyResolvesTest`'s table is for —
     * this enum is in it.
     */
    public function said(): string
    {
        return sprintf('onboarding.%s', $this->value);
    }

    /**
     * The key for the sentence under it.
     *
     * A heading and a sentence rather than one block, because `N1-R54` asks
     * each step to say a thing and `F5` asks a screen to be readable aloud in
     * the order it is drawn — a paragraph carrying its own title is one node to
     * a screen reader and two to a person.
     */
    public function explained(): string
    {
        return sprintf('onboarding.%s_explained', $this->value);
    }
}
