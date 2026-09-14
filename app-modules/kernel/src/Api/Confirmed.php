<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A repair the operator said yes to, and the reading they said it about.
 *
 * **`N2-R5` — confirming is not viewing.** A repair must not be carried out
 * without a confirmation distinct from the act of viewing the finding. The way
 * that requirement is broken is never a deliberate decision: a screen renders a
 * finding, the repair is right there on it, and somewhere a tap handler calls
 * the thing that applies it. Nothing in the code says "this was confirmed",
 * because nothing had to.
 *
 * So carrying out a repair takes one of these, and the only way to make one is
 * {@see self::against()}, which needs the repair *and* the reading named
 * together. Rendering a finding produces no `Confirmed` and cannot be made to;
 * a screen that wants to apply a repair has to write the word.
 *
 * **`N2-R6` — confirmed against one reading, not against readings in general.**
 * The operator agreed to a repair for the situation in front of them. If the
 * stack has moved since, that agreement is about something that is no longer
 * true, and carrying it out applies a decision nobody made about the state it
 * is applied to. {@see self::carriedOut()} compares and answers
 * {@see Carried}, whose refusing arm carries the repair and the new reading so
 * the screen can re-offer rather than leaving somebody in front of a button
 * that did nothing.
 *
 * **It carries the listing the repair was offered in.** `N2-R6` is settled
 * twice and the engine's is the one that counts: it can see whether the machine
 * has moved and this app cannot. A confirmation quotes {@see Offer::named()}
 * and the engine refuses it where the moment has passed. The reading comparison
 * below is the same rule asked on this side, against what the screen still
 * holds — both refuse, and whichever notices first is the one that does.
 *
 * Requiring the offer also makes a confirmation that names a repair the listing
 * never contained impossible to build. That is not a defensive check: the two
 * arrive together and a screen that had lost track of which listing a button
 * belonged to would be confirming against one listing and quoting another.
 *
 * **Identity, not equality.** The comparison is against the reading instance
 * the confirmation was made from. A re-read that happens to produce the same
 * values is still a different reading, and treating it as the same one would
 * mean deciding on the operator's behalf that nothing important changed — which
 * is the judgement `N2-R6` takes away from the app.
 */
final readonly class Confirmed
{
    private function __construct(
        private Repair $repair,
        private Offer $inside,
        private Reading $against,
    ) {}

    /**
     * The one way a confirmation exists.
     *
     * Refuses a retained reading rather than answering about one. A screen that
     * offered confirmation over a reading it knows is old has already broken
     * `N1-R39`, and there is no half-confirmed repair to carry on with — which
     * is the same argument `Pairing::read()` makes for raising.
     */
    public static function against(Repair $repair, Offer $inside, Reading $shown): self
    {
        if (! $shown->mayConfirmAnAction()) {
            throw RepairWasConfirmedAgainstAnOldReading::of($repair);
        }

        if (! $inside->repairs()->holds($repair)) {
            throw RepairWasNotInThatOffer::of($repair);
        }

        return new self($repair, $inside, $shown);
    }

    /**
     * What the engine calls the listing this was agreed to.
     *
     * Published so the port that acts can quote it, and for nothing else. A screen reading it would be about to make a decision from it, and
     * every decision that could be made from it is the engine's — see
     * {@see Offer} for the argument.
     */
    public function quoting(): string
    {
        return $this->inside->named();
    }

    /** Which repair was agreed to, for naming it where the engine is asked. */
    public function repair(): Repair
    {
        return $this->repair;
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
            ? Carried::out($this->repair)
            : Carried::refusedBecauseTheReadingMoved($this->repair, $now);
    }
}
