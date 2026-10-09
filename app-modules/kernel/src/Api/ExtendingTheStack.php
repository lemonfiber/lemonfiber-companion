<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack which plugins it has, what installing, updating or removing one would do, doing it, and what became of that.
 *
 * One port for all of it, for {@see KeepingCurrent}'s reason: they are one
 * conversation, and a port that only read would leave whoever built the yes
 * free to reach a client of their own.
 *
 * **The rehearsal and the install are both work to follow.** The stack answers
 * each with a handle, or refuses it in its own words, and what the work came
 * to is {@see self::whatBecameOf()}'s: the same plugins reading either way.
 *
 * **Each act takes its own agreement** ({@see APluginInstallAgreed},
 * {@see APluginUpdateAgreed}, {@see APluginRemovalAgreed}), which only a
 * rehearsal can produce, so what happens is what was shown and every value
 * sent elsewhere was approved as itself.
 */
interface ExtendingTheStack
{
    /** Which plugins the stack has installed, or what stood in the way. */
    public function installedOn(Stack $stack, Session $session): WhatWasFoundOfThePlugins;

    /** Ask what installing from this source would do, writing nothing. */
    public function rehearseInstalling(Stack $stack, Session $session, APluginSource $source): HowExtendingItIsGoing;

    /** Install it, as the rehearsal the operator was shown said it would. */
    public function install(Stack $stack, Session $session, APluginInstallAgreed $agreed): HowExtendingItIsGoing;

    /** Ask what updating this plugin from the source it was installed from would do, writing nothing. */
    public function rehearseUpdating(Stack $stack, Session $session, APlugin $plugin): HowExtendingItIsGoing;

    /** Update it, as the rehearsal the operator was shown said it would. */
    public function update(Stack $stack, Session $session, APluginUpdateAgreed $agreed): HowExtendingItIsGoing;

    /** Ask what removing this plugin would do, writing nothing. */
    public function rehearseRemoving(Stack $stack, Session $session, APlugin $plugin): HowExtendingItIsGoing;

    /** Remove it, as the rehearsal the operator was shown said it would. */
    public function remove(Stack $stack, Session $session, APluginRemovalAgreed $agreed): HowExtendingItIsGoing;

    /** What became of a rehearsal, an install, an update or a removal, by the handle it answered with. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowExtendingItIsGoing;
}
