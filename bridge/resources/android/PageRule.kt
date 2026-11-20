package app.lemonfiber.native

/**
 * Whether a page outside the app was opened.
 *
 * Deliberately mirrors `PageRule.swift` line for line.
 */
public enum class WhetherThePageOpened(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** The page was handed to the phone's browser. */
    OPENED("opened"),

    /** The address was not an https page, or nothing would open it. */
    REFUSED("refused"),
}

/**
 * Which pages the app opens, and what asking for one came to.
 *
 * Only an https address naming somewhere is opened: nothing else the app is
 * handed leaves it for the browser.
 *
 * Deliberately mirrors `PageRule.swift` line for line.
 */
public data class PageRule(
    /** The address the app asked to open. */
    val address: String,
) {
    /** Whether this address is a page the app opens. */
    public val mayOpen: Boolean
        get() =
            address.startsWith(HTTPS, ignoreCase = true) &&
                address.length > HTTPS.length &&
                address.none { it.isWhitespace() || it.isISOControl() }

    /** What to tell the app, given whether the platform took the request. */
    public fun said(pageWasAskedFor: Boolean): WhetherThePageOpened =
        if (mayOpen && pageWasAskedFor) WhetherThePageOpened.OPENED else WhetherThePageOpened.REFUSED

    private companion object {
        /** The one scheme a page is opened under. */
        const val HTTPS = "https://"
    }
}
