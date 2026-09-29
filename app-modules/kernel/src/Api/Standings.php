<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The word each stack's one line last said, held between sessions.
 *
 * The app opens on the list of stacks, and what each row says under a stack's
 * name is how that stack stands. The core computes that word once, for every
 * surface, and publishes it only on the event stream. A launch draws its first
 * frame before it reaches any stack, so every screen that hears the word keeps
 * it here — the stack's own screen and the list alike — and the list's rows
 * read it back.
 *
 * **What is kept is the word and nothing else.** Not the count, not the worst
 * thing, not the affected items. The row says how a stack stands and the
 * stack's own screen says what is wrong, so keeping the rest would be storing a
 * description of what is wrong with somebody's machine to save a round trip
 * that screen makes anyway.
 *
 * **It is a retained reading, which is the whole reason it may be shown.** A
 * held reading may open a screen, and it has to carry when it was heard, so
 * this answers {@see Showing}, whose held arm is a {@see Reading} that can only
 * be opened by saying what happens for a live one and for a remembered one. A
 * screen cannot show an old word as a current one without having been handed
 * the age and dropped it.
 *
 * **Nothing kept here may confirm an action.** {@see Reading::mayConfirmAnAction()}
 * is where that is answered: everything this port answers is retained by
 * construction, read from a store rather than from a stack.
 */
interface Standings
{
    /**
     * The word this stack's one line last said, or that nothing has been heard yet.
     *
     * `Showing::waiting()` for a stack whose one line has never been heard,
     * which is an ordinary case: pairing and opening a stack are separate
     * screens, and an operator can leave between them.
     */
    public function lastKnownOf(StackId $stack): Showing;

    /**
     * Keep the word a stack's one line just said, with when it was heard.
     *
     * Answers rather than raising: a device that cannot keep the word has lost
     * nothing the operator was promised. The screen that heard it is showing
     * it; the list simply has nothing to say for that stack yet, which is a
     * state this port already has a word for.
     */
    public function remember(StackId $stack, HowItStands $standing, Instant $at): Noted;
}
