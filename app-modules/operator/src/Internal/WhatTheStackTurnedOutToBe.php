<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;

/**
 * One frame's worth of answer, flattened for a template to read.
 *
 * `WhatCameBack::either()` and `Resumed::either()` both answer with an object,
 * so that a caller cannot take a report or a session out without saying what
 * happens when there is none. That is the right shape for a port and the wrong
 * one for a Blade file, which has no `either()` and cannot be given one — so
 * the screen folds three outcomes into this once, and every accessor reads it.
 *
 * **Three outcomes, not two.** The session has ended, the stack was asked and
 * answered, or it was asked and the operator met something. Signed-out is its
 * own state rather than an obstacle because the remedy is a different screen:
 * `N1-R44` sends them to sign in, where every obstacle sends them to look at
 * the machine.
 *
 * `Internal` because it is a detail of how this surface reads two outcomes, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class WhatTheStackTurnedOutToBe
{
    /**
     * @param string   $overall  the key for the headline, or empty where nothing ran
     * @param string   $met      the key for what the operator met, or empty
     * @param string   $remedy   the key for what to do about it, or empty
     * @param Findings $findings what the checks produced, empty where they did not run
     */
    private function __construct(
        public bool $isSignedIn,
        public string $overall,
        public string $met,
        public string $remedy,
        public Findings $findings,
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
        return new self(isSignedIn: false, overall: '', met: '', remedy: '', findings: Findings::none());
    }

    /** The stack answered, and this is what it said. */
    public static function said(Report $report): self
    {
        return new self(
            isSignedIn: true,
            overall: $report->overall()->saidOnTheScreen(),
            met: '',
            remedy: '',
            findings: $report->findings(),
        );
    }

    /**
     * It did not, and this is what the operator met.
     *
     * The keys come off {@see Obstacle} rather than being spelled, which is the
     * same derivation every other screen in this application uses — and it is
     * why an obstacle gaining a seventh case needs no edit here.

     * **A credential the stack refused is a signed-out app, not an obstacle.**
     * `N3-R13` says an identity removed from the household results in a
     * signed-out app at the next refused call and that nothing already loaded
     * goes on being rendered. {@see Obstacle::meansWeAreSignedOut()} draws that
     * line once, so this fold and the four beside it cannot come to disagree
     * about whether somebody is signed in.
     */
    public static function met(Obstacle $why): self
    {
        if ($why->meansWeAreSignedOut()) {
            return self::signedOut();
        }

        return new self(
            isSignedIn: true,
            overall: '',
            met: $why->said(),
            remedy: $why->remedy(),
            findings: Findings::none(),
        );
    }
}
