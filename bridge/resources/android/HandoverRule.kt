package app.lemonfiber.native

/**
 * Why the sheet was not put in front of anybody.
 *
 * Two refusals and they are not the same sentence. *There was nothing to hand
 * over* is this application's own fault and is answered by assembling the
 * report again; *the platform would not* is the device's, and is answered by
 * trying again or by giving up on that road. A boolean cannot carry that, and a
 * screen behind one has to guess which it was.
 *
 * Deliberately mirrors `HandoverRule.swift` line for line.
 */
public enum class WhyNothingWasHandedOver(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** The file was not there to offer. Nothing was put in front of anybody. */
    NOTHING_TO_HAND_OVER("nothing_to_hand_over"),

    /** It was there and the sheet could not be presented. */
    THE_PLATFORM_WOULD_NOT("the_platform_would_not"),
}

/**
 * Whether the sheet can be offered, and why not where it cannot.
 *
 * **There is deliberately no answer for what the operator chose.** A handover
 * ends with them picking an app, with them dismissing the sheet, or with the
 * platform never presenting it. The first two are one answer here: where the
 * report went is none of this application's business, and an app that watched
 * where it went would not be honouring *assembled for the operator to send,
 * not sent*. Only the third is its own, because a sheet that never appeared
 * leaves somebody looking at a screen that did nothing.
 *
 * No Android framework in sight. Everything is a reading of two facts a caller
 * passes in, which is what lets it be run on a JVM in two seconds rather than
 * demonstrated on a handset.
 *
 * Deliberately mirrors `HandoverRule.swift` line for line.
 */
public data class HandoverRule(
    /** Whether there is a file at the path the caller named. */
    public val thereIsSomethingToHandOver: Boolean,
    /** Whether the platform presented the sheet when it was asked to. */
    public val theSheetWasPresented: Boolean,
) {
    /** Whether the operator was shown the sheet at all. */
    public val wasOffered: Boolean
        get() = thereIsSomethingToHandOver && theSheetWasPresented

    /**
     * Why they were not, or nothing because they were.
     *
     * Read in this order and the order is load-bearing: a missing file is not a
     * platform that would not present, and reporting it as one sends somebody
     * to try again at a thing that will fail the same way every time.
     */
    public val whyNot: WhyNothingWasHandedOver?
        get() =
            when {
                !thereIsSomethingToHandOver -> WhyNothingWasHandedOver.NOTHING_TO_HAND_OVER
                !theSheetWasPresented -> WhyNothingWasHandedOver.THE_PLATFORM_WOULD_NOT
                else -> null
            }

    /** Where the starting state lives, named so it reads at a call site. */
    public companion object {
        /** A report that is there and a sheet that opened: the ordinary handover. */
        public val OFFERED: HandoverRule =
            HandoverRule(thereIsSomethingToHandOver = true, theSheetWasPresented = true)
    }
}
