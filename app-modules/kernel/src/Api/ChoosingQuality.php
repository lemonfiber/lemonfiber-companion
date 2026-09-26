<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How good a stack's media should be, asked of the stack rather than decided here.
 *
 * **Choosing and confirming are two methods.** The stack records a choice as
 * soon as it is asked, except one this machine would have to transcode in
 * software, which it holds and says why. Confirming that one is a second act
 * with its own argument, {@see AHeldChoice}, which only a held answer makes —
 * so seeing a held choice and agreeing to it cannot be the same call.
 *
 * Putting a preset back over a hand-edited configuration is not here. Its
 * whole effect is overwriting what the operator changed, and this app does not
 * offer that.
 */
interface ChoosingQuality
{
    /**
     * The quality in force, and whether the configuration was edited by hand.
     *
     * Answers {@see WhatWasFoundOfTheQuality} rather than raising, which `C1`
     * requires.
     */
    public function inForceOn(Stack $stack, Session $session): WhatWasFoundOfTheQuality;

    /** Choose this preset; the stack records it, or holds it and says why. */
    public function choose(Stack $stack, Session $session, APresetToChoose $asked): WhatTheChoiceCameTo;

    /** Choose a held preset after all, having been shown why it was held. */
    public function confirm(Stack $stack, Session $session, AHeldChoice $agreed): WhatTheChoiceCameTo;
}
