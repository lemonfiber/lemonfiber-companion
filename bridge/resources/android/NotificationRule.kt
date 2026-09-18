package app.lemonfiber.native

/**
 * Whether a notification would be seen, and whether anybody may still be asked.
 *
 * The four facts a platform can report about notifications, and what they mean
 * together. Reconstructing the three answers out of them is
 * [WhatTheOperatorSaid]'s, because the camera asks the identical question and
 * two copies of that decision would be two capabilities disagreeing quietly.
 * What is here is the part that belongs to telling somebody: which facts are
 * gathered, and what the answer is called once the subject is a notification.
 *
 * No Android framework in sight. Everything is a reading of four booleans a
 * caller passes in, which is what lets it be run on a JVM in two seconds rather
 * than demonstrated on a handset.
 *
 * Deliberately mirrors `NotificationRule.swift` line for line.
 */
public data class NotificationRule(
    /**
     * Whether a notification posted right now would be seen.
     *
     * One fact covering three switches — the runtime permission, the channel,
     * and the application's own toggle in system settings — because the
     * operator turning any of them off means the same thing to a caller with
     * something to show.
     */
    public val wouldAppear: Boolean,
    /**
     * Whether this platform has a runtime permission to ask for at all.
     *
     * False below Android 33, where notifications are granted at install time
     * and turned off in settings afterwards; always true on iOS. Taken as an
     * input rather than branched on inside, so that the version check stays in
     * the shim where the platform is and the decision stays here where it can
     * be run.
     */
    public val permissionIsAsked: Boolean,
    /**
     * Whether the platform says an explanation would help.
     *
     * True only after a refusal, which is what makes it evidence of one rather
     * than a suggestion. It is never true before the first prompt and never
     * true after a permanent one, so it identifies exactly one of the three
     * states and cannot stand alone. Always false on iOS, which has nothing of
     * the kind.
     */
    public val wouldExplain: Boolean,
    /**
     * Whether this application has ever raised the prompt.
     *
     * Kept by this plugin because nothing else can keep it: Android has no
     * call that answers it, and the two states it separates — never asked, and
     * refused so firmly the platform stopped offering — are identical from the
     * outside. Recorded where the prompt is raised rather than inferred
     * afterwards.
     */
    public val everAsked: Boolean,
) {
    /** What the operator has said, as far as anything can tell. */
    public val said: WhatTheOperatorSaid
        get() = WhatTheOperatorSaid.readFrom(wouldAppear, permissionIsAsked, wouldExplain, everAsked)

    /**
     * Whether the prompt may be raised now.
     *
     * Named here as well as on the answer because the shim asks this question
     * of the rule rather than of the word: what a caller has in hand is the
     * four facts, and the two readings of them belong side by side.
     */
    public val mayAsk: Boolean
        get() = said.mayAsk

    /** Whether something posted now would reach the operator. */
    public val mayShow: Boolean
        get() = said.mayProceed

    /** Where the starting state lives, named so it reads at a call site. */
    public companion object {
        /**
         * A device nobody has asked anything, on a platform that asks.
         *
         * What a first launch looks like on Android 33 and above, and the state
         * the other cases are written as a departure from.
         */
        public val UNASKED: NotificationRule =
            NotificationRule(
                wouldAppear = false,
                permissionIsAsked = true,
                wouldExplain = false,
                everAsked = false,
            )
    }
}
