package app.lemonfiber.native

/**
 * What every function in this bridge answers with.
 *
 * `outcome` is always there and is always one of a closed set the capability's
 * own page lists. `because` is there only where the outcome is a refusal and is
 * likewise closed. Neither ever carries a value the caller passed in — no key,
 * no token, no address, no scanned payload — which is what makes a refusal safe
 * to put in a log line.
 *
 * **Its own file, and a type rather than a `mapOf` at each call site.** Three
 * capabilities answer refusals and a fourth answers a value beside one, and the
 * promise above is a promise about all of them at once. Written out wherever an
 * answer is built it is a convention, which is the word this repository uses
 * for the thing that holds until somebody new arrives; written here it is one
 * place a reader — or a check — can stand.
 *
 * **A refusal cannot carry anything, and that is structural.** There is no
 * factory that takes a reason and a payload together, so the mistake is not
 * available: a refusal that carried the value it refused to hand over would be
 * the whole point of refusing, undone.
 *
 * `@ConsistentCopyVisibility` is what makes that hold. A `data class` generates
 * a `copy()` with the class's visibility rather than the constructor's, so
 * without it the private constructor is public again under another name and the
 * factories above are a suggestion.
 *
 * Deliberately mirrors `Envelope.swift` line for line.
 */
@ConsistentCopyVisibility
public data class Envelope private constructor(
    /** What became of the call, as a word. */
    public val outcome: String,
    /** Why, where the outcome is a refusal, and never otherwise. */
    public val because: String?,
    /** What the call was asked for, where it is something other than an outcome. */
    public val carrying: Map<String, Any>,
) {
    /**
     * This answer as the bridge hands it back.
     *
     * The keys are built here rather than at each handler so that `because`
     * cannot appear beside a success and cannot go missing from a refusal.
     *
     * The envelope's own two keys are written after the payload rather than
     * before it, so that something carried can never take the place of the
     * outcome — a payload keyed `outcome` would otherwise be the one answer a
     * caller reads, chosen by whoever named the field.
     */
    public fun asAnswer(): Map<String, Any> =
        buildMap {
            putAll(carrying)
            put("outcome", outcome)
            because?.let { put("because", it) }
        }

    /** Where an answer is built, and the only place one can be. */
    public companion object {
        /** An answer that is only its outcome. */
        public fun of(outcome: String): Envelope =
            Envelope(outcome = outcome, because = null, carrying = emptyMap())

        /**
         * An answer that carries what it was asked for.
         *
         * The payload is a value this application asked the platform for — a
         * list of what is still to come, a value that was kept. It sits beside
         * the outcome rather than inside it, so that a caller reading only the
         * outcome reads a closed word either way.
         */
        public fun of(
            outcome: String,
            carrying: Map<String, Any>,
        ): Envelope = Envelope(outcome = outcome, because = null, carrying = carrying)

        /**
         * A refusal, and the closed word for why.
         *
         * The outcome is passed in rather than fixed, because the word for a
         * refusal is the capability's: a write is `refused`, a notification is
         * `withheld`, a scan that found nothing is `nothing`. What is fixed is
         * that a reason never appears without one.
         */
        public fun refusing(
            outcome: String,
            because: String,
        ): Envelope = Envelope(outcome = outcome, because = because, carrying = emptyMap())

        /**
         * A refusal that also says whether asking again could change it.
         *
         * The one thing a refusal may carry, and it has a factory of its own
         * rather than a payload parameter on the one above. The distinction is
         * the whole reason: `may_ask_again` is a fact about the refusal, closed
         * and named here, while a payload is whatever a caller handed in. A
         * general `refusing(outcome, because, carrying)` would make the two
         * indistinguishable at a call site and put the general payload slot
         * back on the arm that must never have one.
         *
         * The question it answers is the same on every permission: a refusal
         * given in the dialog a moment ago can be put again, and one settled in
         * settings cannot and has to send the operator there instead. A screen
         * has no other way to choose between those two sentences.
         */
        public fun refusing(
            outcome: String,
            because: String,
            mayAskAgain: Boolean,
        ): Envelope =
            Envelope(
                outcome = outcome,
                because = because,
                carrying = mapOf("may_ask_again" to mayAskAgain),
            )
    }
}
