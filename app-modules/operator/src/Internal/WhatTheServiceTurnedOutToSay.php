<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Scrollback;

/**
 * What reading one service's tail produced, flattened for a template.
 *
 * The sibling of {@see WhatStoppedTurnedOutToBe} and written the same way: a
 * screen folds the answer once and the template reads fields, because Blade has
 * no `either()` and cannot be given one.
 *
 * **Three states, and the silent service is one of them.** A service that has
 * said nothing in the lines that were asked for is running quietly; a session
 * that has ended is `N1-R44`'s screen; an obstacle is `N1-R10`'s. Folding the
 * first two together would have a signed-out phone report a quiet service,
 * which is the collapse {@see \Modules\Kernel\Api\WhatWasSaid} refuses one
 * layer up and this one must not rebuild.
 *
 * **What arrived and what is shown are separate numbers**, because a search
 * narrows the second and must not narrow the first. A window of two hundred
 * filtered to twelve is still a window that stopped at two hundred, and a
 * screen that recomputed the claim from the twelve would tell an operator the
 * search covered everything the service ever said — which is the one lie this
 * screen can tell that somebody would act on.
 */
final readonly class WhatTheServiceTurnedOutToSay
{
    /**
     * @param bool                  $isSignedIn whether this device still holds a session for the stack
     * @param string                $met        the key for what stood in the way, or empty where nothing did
     * @param string                $remedy     the key for what to do about it, or empty where nothing did
     * @param list<WhatOneLineSays> $lines      the lines to show, oldest first
     * @param int                   $arrived    how many came back before anything narrowed them
     * @param int                   $bound      how many were asked for (`N2-R10`)
     * @param bool                  $isAWindow  whether the view stops where it was told to
     * @param bool                  $isSearching whether a search is narrowing the lines
     */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public array $lines,
        public int $arrived,
        public int $bound,
        public bool $isAWindow,
        public bool $isSearching,
    ) {}

    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. `N1-R44`'s screen is where this goes, and the empty keys are what
     * the template branches on.
     */
    public static function signedOut(): self
    {
        return new self(
            isSignedIn: false,
            met: '',
            remedy: '',
            lines: [],
            arrived: 0,
            bound: 0,
            isAWindow: false,
            isSearching: false,
        );
    }

    /** The stack answered, and this is the window, narrowed to what was typed. */
    public static function this(Scrollback $scrollback, LookingFor $looking): self
    {
        $shown = $scrollback->matching($looking);

        $rows = [];

        foreach ($shown as $line) {
            $rows[] = WhatOneLineSays::in($line);
        }

        // Every claim about the edge comes off the window rather than off the
        // rows, which is what keeps a search from quietly widening it.
        return new self(
            isSignedIn: true,
            met: '',
            remedy: '',
            lines: $rows,
            arrived: $shown->howManyArrived(),
            bound: $shown->asked()->figure(),
            isAWindow: $shown->isAWindow(),
            isSearching: $shown->lookingFor()->isSearching(),
        );
    }

    /**
     * It did not, and this is what the operator met.
     *
     * The keys come off {@see Obstacle}, which owns them — so an obstacle
     * gaining a seventh case needs no edit here and cannot be given a sentence
     * here that disagrees with the one another screen shows.
     *
     * **A credential the stack refused is a signed-out app, not an obstacle.**
     * `N3-R13` says an identity removed from the household results in a
     * signed-out app at the next refused call and that nothing already loaded
     * goes on being rendered. {@see Obstacle::meansWeAreSignedOut()} draws that
     * line once, so this fold and the four beside it cannot come to disagree
     * about whether somebody is signed in — and a window already fetched is not
     * shown under a sentence about a machine.
     */
    public static function met(Obstacle $why): self
    {
        if ($why->meansWeAreSignedOut()) {
            return self::signedOut();
        }

        return new self(
            isSignedIn: true,
            met: $why->said(),
            remedy: $why->remedy(),
            lines: [],
            arrived: 0,
            bound: 0,
            isAWindow: false,
            isSearching: false,
        );
    }
}
