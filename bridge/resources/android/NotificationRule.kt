package app.lemonfiber.native

/**
 * What the device would do with a notification, read rather than asked.
 *
 * The distinction this exists for is between *nobody has been asked* and *the
 * operator said no*. An app that cannot tell them apart prompts a second time on
 * a device where somebody already refused, which is the one thing the rule about
 * a declined permission forbids — and it is the easy mistake, because Android
 * answers both with the same `false`.
 *
 * No Android framework here. Everything is arithmetic on values a caller passes
 * in, which is what lets the decision be tested on a JVM in two seconds rather
 * than demonstrated on a handset with its notifications switched off.
 *
 * Deliberately mirrors `NotificationRule.swift` line for line, for the reason
 * the capture rule and the lock rule are mirrored: two platforms disagreeing
 * about whether somebody has been asked is a bug nobody finds, because each half
 * looks right on its own.
 */
public data class NotificationRule(
    /**
     * Whether a notification posted now would actually appear.
     *
     * The whole answer where it is true, and deliberately the first question:
     * it covers the runtime permission, the per-channel switch and the
     * app-level switch at once, and any of those being off means the same thing
     * to somebody waiting to be told something.
     */
    public val wouldAppear: Boolean,
    /**
     * Whether this Android has a runtime permission for notifications at all.
     *
     * False below API 33, where there is nothing to ask for. Notifications off
     * there means the operator turned them off in settings, which is a refusal
     * already given rather than a question still open — and prompting for a
     * permission the platform does not have would do nothing at all.
     */
    public val permissionIsAsked: Boolean,
    /**
     * Whether Android says an explanation would help.
     *
     * True only after a refusal, which makes it evidence of one. It is never
     * true before the first prompt and never true after a permanent refusal, so
     * it identifies exactly one of the three states and cannot stand alone.
     */
    public val wouldExplain: Boolean,
    /**
     * Whether this application has ever raised the prompt.
     *
     * Kept by this plugin because nothing else can keep it: Android has no call
     * that answers it, and the two states it separates — never asked, and
     * refused so firmly that the platform stopped offering — are identical from
     * the outside. Recorded where the prompt is raised rather than inferred.
     */
    public val everAsked: Boolean,
) {
    /**
     * The three answers the application reasons in, as the wire spells them.
     *
     * Strings rather than an enum across the bridge, matching what the platform
     * facade already answers, so the reading side keeps one vocabulary.
     */
    public fun said(): String =
        when {
            wouldAppear -> GRANTED
            !permissionIsAsked -> DENIED
            wouldExplain -> DENIED
            everAsked -> DENIED
            else -> NOT_DETERMINED
        }

    /** The three words the wire spells these answers with. */
    public companion object {
        /** A notification posted now would appear. */
        public const val GRANTED: String = "granted"

        /** It would not, and nothing may ask again. */
        public const val DENIED: String = "denied"

        /** Nobody has been asked; the point of first use is still ahead. */
        public const val NOT_DETERMINED: String = "not_determined"
    }
}
