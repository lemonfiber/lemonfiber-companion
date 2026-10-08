<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack which plugins it has, what installing one would do, installing it, and what became of that.
 *
 * One port for the four, for {@see KeepingCurrent}'s reason: they are one
 * conversation, and a port that only read would leave whoever built the yes
 * free to reach a client of their own.
 *
 * **The rehearsal and the install are both work to follow.** The stack answers
 * each with a handle, or refuses it in its own words, and what the work came
 * to is {@see self::whatBecameOf()}'s: the same plugins reading either way.
 *
 * **Installing takes an {@see APluginInstallAgreed}**, which only a rehearsal
 * can produce, so what is installed is what was shown and every value it
 * sends elsewhere was approved as itself.
 */
interface ExtendingTheStack
{
    /** Which plugins the stack has installed, or what stood in the way. */
    public function installedOn(Stack $stack, Session $session): WhatWasFoundOfThePlugins;

    /** Ask what installing from this source would do, writing nothing. */
    public function rehearseInstalling(Stack $stack, Session $session, APluginSource $source): HowExtendingItIsGoing;

    /** Install it, as the rehearsal the operator was shown said it would. */
    public function install(Stack $stack, Session $session, APluginInstallAgreed $agreed): HowExtendingItIsGoing;

    /** What became of a rehearsal or an install, by the handle it answered with. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowExtendingItIsGoing;
}
