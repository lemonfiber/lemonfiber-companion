<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Obstacle;

/**
 * What asking a stack what it is running produced, flattened for a template.
 *
 * The sibling of {@see WhatStoppedTurnedOutToBe} and written the same way: a
 * screen folds the answer once and the template reads fields, because Blade has
 * no `either()` and cannot be given one.
 *
 * **Three states, and a stack running nothing is one of them.** Everything off
 * is the answer an operator opens this screen to change; a session that has
 * ended is `N1-R44`'s screen; an obstacle is `N1-R10`'s. Folding the first two
 * together would have a signed-out phone report a house where nothing is
 * running, which is the collapse {@see \Modules\Kernel\Api\WhatIsRunning}
 * refuses one layer up and this one must not rebuild.
 *
 * **The forms are carried whether or not anything in them is running.** A form
 * with everything stopped is the one an operator came here to start, and a
 * listing assembled from the rows would not have it.
 *
 * **Whether anything is settling is decided over the rows, once.** The screen's
 * cadence reads it and the template states it, and those two have to agree:
 * a screen saying *this is starting* while the poll had stopped would leave
 * somebody watching a sentence that will never change.
 */
final readonly class WhatThisStackRunsTurnedOutToBe
{
    /**
     * @param bool                     $isSignedIn whether this device still holds a session for the stack
     * @param string                   $met        the key for what stood in the way, or empty where nothing did
     * @param string                   $remedy     the key for what to do about it, or empty where nothing did
     * @param list<WhatOneServiceSays> $services   everything it runs, in the stack's order
     * @param list<string>             $forms      the forms it has, whether or not anything in them runs
     * @param string                   $overall    the key for what it all amounts to, or empty where there is none
     * @param bool                     $isSettling whether anything here becomes something else by itself
     */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public array $services,
        public array $forms,
        public string $overall,
        public bool $isSettling,
        public ?Disturbances $disturbs,
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
            services: [],
            forms: [],
            overall: '',
            isSettling: false,
            disturbs: null,
        );
    }

    /** The stack answered, and this is what it is running. */
    public static function these(Daemons $daemons): self
    {
        $rows = [];
        $settling = false;

        foreach ($daemons as $daemon) {
            $row = WhatOneServiceSays::in($daemon);
            $rows[] = $row;
            $settling = $settling || $row->isSettling;
        }

        $forms = [];

        foreach ($daemons->forms() as $form) {
            $forms[] = $form->named();
        }

        // The overall comes off the listing rather than off the rows, so what
        // the stack amounts to is decided where the stack said it — a fold that
        // dropped a row could never quietly turn a degraded machine into a
        // healthy one.
        return new self(
            isSignedIn: true,
            met: '',
            remedy: '',
            services: $rows,
            forms: $forms,
            overall: $daemons->running()->saidOnTheScreen(),
            isSettling: $settling,
            disturbs: $daemons->disturbs(),
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
     * signed-out app at the next refused call, and that nothing already loaded
     * goes on being rendered — which matters more here than on a listing
     * nobody acts from: what is already loaded on this screen is six buttons
     * that change somebody's machine.
     * {@see Obstacle::meansWeAreSignedOut()} draws the line once, so this fold
     * and the five beside it cannot come to disagree about it.
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
            services: [],
            forms: [],
            overall: '',
            isSettling: false,
            disturbs: null,
        );
    }
}
