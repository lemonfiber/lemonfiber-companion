package app.lemonfiber.native

/**
 * Whether this app's settings page was asked for.
 *
 * Two answers and no third.
 *
 * Deliberately mirrors `SettingsRule.swift` line for line.
 */
public enum class WhetherTheSettingsOpened(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** The page was asked for, and the platform has it in front of the operator. */
    OPENED("opened"),

    /** The platform would not open it. The screen says so. */
    REFUSED("refused"),
}

/**
 * What asking for the settings page came to.
 *
 * Deliberately mirrors `SettingsRule.swift` line for line.
 */
public data class SettingsRule(
    /** Whether the platform took the request for the page. */
    val pageWasAskedFor: Boolean,
) {
    /** What to tell the app. */
    public val said: WhetherTheSettingsOpened
        get() = if (pageWasAskedFor) WhetherTheSettingsOpened.OPENED else WhetherTheSettingsOpened.REFUSED
}
