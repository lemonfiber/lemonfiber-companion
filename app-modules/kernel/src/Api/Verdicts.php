<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What each stack last came to, held between sessions.
 *
 * `N2-R1` says the app opens on the overall verdict, and the ordering is the
 * whole design: *is anything wrong*, then *what*, then *may I fix it from
 * here*. Until this existed the app opened on a list of machines and the names
 * their owner gave them, which answers a question nobody opened the app to ask
 * — the verdict was two taps and a network round trip away, behind whichever
 * stack they guessed at first.
 *
 * **What is kept is the word and nothing else.** Not the findings, not the
 * codes, not which service. `N2-R1` asks for the verdict on opening and the
 * findings are what the next screen is for, so keeping them would be storing a
 * description of what is wrong with somebody's machine to save a round trip
 * that screen makes anyway.
 *
 * **It is a retained reading, which is the whole reason it is allowed.**
 * `N1-R24` permits a held reading to open a screen and `N1-R9` requires it
 * carry when it was read — so this answers {@see Showing}, whose arms are
 * *waiting* and *holding*, and the held one is a {@see Reading} that can only
 * be opened by saying what happens for a live one and for a remembered one.
 * A screen cannot show yesterday's verdict as today's without having been
 * handed the age and dropped it.
 *
 * **Nothing kept here may confirm an action.** `N1-R24`'s second clause, and
 * {@see Reading::mayConfirmAnAction()} is where it is answered: a remembered
 * *healthy* shown after a restart that failed is the app lying at the one
 * moment the operator was watching. Everything this port answers is retained by
 * construction — it is read from a store, not from a stack — so the screens
 * that ask a stack keep asking it.
 */
interface Verdicts
{
    /**
     * What this stack last came to, or that nothing has been read yet.
     *
     * `Showing::waiting()` for a stack paired but never asked, which is the one
     * case `N1-R28` lets a progress indicator answer — and it is a real case
     * here rather than a defensive one, because pairing and asking are separate
     * screens and an operator can leave between them.
     */
    public function lastKnownOf(StackId $stack): Showing;

    /**
     * Keep what a stack just came to, so the next opening has it.
     *
     * Answers rather than raising, for `C1`'s reason and a second one: a device
     * that cannot keep a verdict has lost nothing the operator was promised.
     * The screen that asked has its live answer and shows it; the next opening
     * simply has nothing to open on, which is a state this port already has a
     * word for.
     */
    public function remember(StackId $stack, Overall $overall, Instant $at): Noted;
}
