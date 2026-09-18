package app.lemonfiber.native

/**
 * What the operator has said about a permission, and what may be done about it.
 *
 * Three answers rather than two, and the third is the whole point. *Nobody has
 * been asked* and *somebody said no* are the same answer to "may I do this" and
 * opposite answers to "may I ask" — an application that cannot tell them apart
 * either prompts somebody who already refused, every time they open the screen,
 * or never asks anybody at all.
 *
 * **Its own file because more than one capability needs it.** Notifications ask
 * this question and so does the camera, and the three states are the same three
 * in both: a refusal in the dialog just now can be asked about again, a refusal
 * settled some time ago cannot, and nobody having been asked is neither. Left
 * inside one capability's rule the second would either import a type named
 * after something unrelated or — far more likely — declare its own near-copy,
 * and then two capabilities would quietly disagree about what `denied` means.
 *
 * The other reason is the wire. `outcome` and `because` are closed sets that
 * never carry a value the caller passed in, and a closed set kept in one place
 * is a promise something can be made to check; one copied into each capability
 * is a promise nothing can stand over.
 *
 * The word is what crosses the wire. A number would be smaller and worse: a log
 * line reading `not_determined` explains itself to whoever is reading it, and
 * one reading `2` sends them to a file.
 *
 * Deliberately mirrors `WhatTheOperatorSaid.swift` line for line.
 */
public enum class WhatTheOperatorSaid(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** The thing the permission guards would work right now. */
    GRANTED("granted"),

    /** It would not, and nothing may ask again. */
    DENIED("denied"),

    /** Nobody has been asked; the point of first use is still ahead. */
    NOT_DETERMINED("not_determined"),
    ;

    /**
     * Whether raising the prompt now could change the answer.
     *
     * Deliberately not the negation of [mayProceed]. A grant and a refusal are
     * both reasons not to ask and opposite answers to whether anything may
     * happen, so a caller reading one for the other is an application that
     * prompts on every screen or never prompts at all.
     */
    public val mayAsk: Boolean
        get() = this == NOT_DETERMINED

    /** Whether the thing the permission guards may happen now. */
    public val mayProceed: Boolean
        get() = this == GRANTED

    /** Where the platform's facts become one of the three. */
    public companion object {
        /**
         * The three answers, reconstructed from what Android is willing to report.
         *
         * `areNotificationsEnabled()` and `checkSelfPermission()` answer false
         * for *never asked* and for *refused forever* alike, and
         * `shouldShowRequestPermissionRationale` is true only in the narrow
         * window between a first refusal and a permanent one. Telling the three
         * apart needs a record of whether this application has ever raised the
         * prompt, and nothing in the platform keeps one — so the bridge keeps
         * it, written where the prompt is raised rather than reconstructed
         * afterwards.
         *
         * Read in this order and the order is load-bearing. Something that
         * would work settles it whatever else is true: an operator who refused
         * once and allowed it again in settings has allowed it. After that
         * every remaining answer is a refusal of some kind, and the last case
         * standing is the one nobody has answered yet.
         *
         * @param wouldAppear whether the thing the permission guards would work now.
         * @param permissionIsAsked whether this platform has a runtime permission at all.
         * @param wouldExplain whether the platform says an explanation would help.
         * @param everAsked whether this application has ever raised the prompt.
         */
        public fun readFrom(
            wouldAppear: Boolean,
            permissionIsAsked: Boolean,
            wouldExplain: Boolean,
            everAsked: Boolean,
        ): WhatTheOperatorSaid =
            when {
                wouldAppear -> GRANTED
                !permissionIsAsked -> DENIED
                wouldExplain -> DENIED
                everAsked -> DENIED
                else -> NOT_DETERMINED
            }
    }
}
