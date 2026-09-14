<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;

/**
 * What asking a stack what it would put right came to, flattened for a template.
 *
 * Five states, and they are five because {@see \Modules\Kernel\Api\Mending} is
 * two questions: the asking can fail on its own, and so can the reading, and
 * the reading has three answers of its own. A screen that folded any pair of
 * them together would be a screen with a wrong sentence for a real situation.
 *
 * - **signed out** — no session, so nothing was asked (`N1-R44`).
 * - **still working it out** — the stack took the question on and has not
 *   finished. The remedy is to ask again, and it is the operator's to take.
 * - **offering** — the listing, which is what `N2-R4` is about.
 * - **the job ended** — the stack has no outcome for that handle any more. Not
 *   a fault and not an answer: start again.
 * - **met an obstacle** — the machine could not be reached at all.
 *
 * **Nothing carries the handle.** A template has no use for it and `N1-R41` is
 * emphatic that an action must not be presented as pending — a job name on the
 * glass is exactly that, dressed as a diagnostic. The screen holds it; this
 * does not.
 */
final readonly class WhatTheStackWouldPutRight
{
    /**
     * @param bool                   $isSignedIn whether this device still holds a session for the stack
     * @param bool                   $isWorking  whether the stack is still working out what it would do
     * @param bool                   $hasEnded   whether the stack has no outcome for that job any more
     * @param string                 $named      the listing's name, or empty where there is no listing
     * @param list<WhatOneRepairSays> $repairs   what it would put right, in the order offered
     * @param string                 $met        the key for what stood in the way, or empty where nothing did
     * @param string                 $remedy     the key for what to do about it, or empty where nothing did
     */
    private function __construct(
        public bool $isSignedIn,
        public bool $isWorking,
        public bool $hasEnded,
        public string $named,
        public array $repairs,
        public string $met,
        public string $remedy,
    ) {}

    /** No session for that stack, so nothing was asked (`N1-R44`). */
    public static function signedOut(): self
    {
        return new self(isSignedIn: false, isWorking: false, hasEnded: false, named: '', repairs: [], met: '', remedy: '');
    }

    /** The stack is still working out what it would do. */
    public static function stillWorkingItOut(): self
    {
        return new self(isSignedIn: true, isWorking: true, hasEnded: false, named: '', repairs: [], met: '', remedy: '');
    }

    /** It finished, and this is the listing. */
    public static function offering(Offer $offer): self
    {
        $rows = [];

        foreach ($offer->repairs() as $repair) {
            $rows[] = WhatOneRepairSays::in($repair);
        }

        // The listing's name is carried even though no template shows it.
        // `N2-R6` has a yes quote the listing it was given, and the screen that
        // will offer that yes reads it from here — a fold that dropped it would
        // have to ask the stack again to agree to what it is already showing.
        return new self(
            isSignedIn: true,
            isWorking: false,
            hasEnded: false,
            named: $offer->named(),
            repairs: $rows,
            met: '',
            remedy: '',
        );
    }

    /** The stack has no outcome for that job any more. */
    public static function ended(): self
    {
        return new self(isSignedIn: true, isWorking: false, hasEnded: true, named: '', repairs: [], met: '', remedy: '');
    }

    /** The machine could not be reached, and this is what the operator met. */
    public static function met(Obstacle $why): self
    {
        return new self(
            isSignedIn: true,
            isWorking: false,
            hasEnded: false,
            named: '',
            repairs: [],
            met: $why->said(),
            remedy: $why->remedy(),
        );
    }
}
