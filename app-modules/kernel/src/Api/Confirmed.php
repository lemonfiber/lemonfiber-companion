<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A repair the operator said yes to, and the reading they said it about.
 *
 * **`N2-R5` — confirming is not viewing.** A repair must not be carried out
 * without a confirmation distinct from the act of viewing the finding. The way
 * that requirement is broken is never a deliberate decision: a screen renders a
 * finding, the remedy is right there on it, and somewhere a tap handler calls
 * the thing that applies it. Nothing in the code says "this was confirmed",
 * because nothing had to.
 *
 * So carrying out a repair takes one of these, and the only way to make one is
 * {@see self::against()}, which needs the remedy *and* the reading named
 * together. Rendering a finding produces no `Confirmed` and cannot be made to;
 * a screen that wants to apply a repair has to write the word.
 *
 * **`N2-R6` — confirmed against one reading, not against readings in general.**
 * The operator agreed to a repair for the situation in front of them. If the
 * stack has moved since, that agreement is about something that is no longer
 * true, and carrying it out applies a decision nobody made about the state it
 * is applied to. {@see self::carriedOut()} compares and answers
 * {@see Carried}, whose refusing arm carries the remedy and the new reading so
 * the screen can re-offer rather than leaving somebody in front of a button
 * that did nothing.
 *
 * **Identity, not equality.** The comparison is against the reading instance
 * the confirmation was made from. A re-read that happens to produce the same
 * values is still a different reading, and treating it as the same one would
 * mean deciding on the operator's behalf that nothing important changed — which
 * is the judgement `N2-R6` takes away from the app.
 */
final readonly class Confirmed
{
    private function __construct(private Remedy $remedy, private Reading $against) {}

    /**
     * The one way a confirmation exists.
     *
     * Refuses a retained reading rather than answering about one. A screen that
     * offered confirmation over a reading it knows is old has already broken
     * `N1-R39`, and there is no half-confirmed repair to carry on with — which
     * is the same argument `Pairing::read()` makes for raising.
     */
    public static function against(Remedy $remedy, Reading $shown): self
    {
        if (! $shown->mayConfirmAnAction()) {
            throw RepairWasConfirmedAgainstAnOldReading::of($remedy);
        }

        return new self($remedy, $shown);
    }

    /**
     * Carry it out, if the reading is still the one it was confirmed against.
     *
     * Takes the reading rather than being asked whether it may proceed: a
     * `mayProceed()` answering true is a question a caller can forget to ask,
     * and the forgetting looks like working code. Handing the current reading
     * in is the only way to get an answer at all.
     */
    public function carriedOut(Reading $now): Carried
    {
        return $now === $this->against
            ? Carried::out($this->remedy)
            : Carried::refusedBecauseTheReadingMoved($this->remedy, $now);
    }
}
