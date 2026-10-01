package app.lemonfiber.native

/**
 * Whether the platform refuses this app the local network on the way to one address.
 *
 * Two answers and no third.
 *
 * Deliberately mirrors `LocalNetworkRule.swift` line for line.
 */
public enum class WhetherTheLocalNetworkIsOpen(
    public val word: String,
) {
    /** Nothing about this app is refused on the way. The app reports what it meets. */
    OPEN("open"),

    /** The platform refuses this app the local network. The remedy is a switch in Settings. */
    FORBIDDEN("forbidden"),
}

/**
 * What the platform's account of one path means.
 *
 * Android asks no permission before an app reaches the local network, so this
 * half is only ever handed [UNASKED]; the rule is the same one iOS reads, so
 * both halves answer the same question the same way.
 *
 * Deliberately mirrors `LocalNetworkRule.swift` line for line.
 */
public data class LocalNetworkRule(
    /** Whether the platform said anything about the path before the question was given up on. */
    public val pathWasReadable: Boolean,
    /** Whether the reason the path cannot be taken is that this app may not use the local network. */
    public val platformDeniedIt: Boolean,
) {
    /** What to tell the app: a platform that said nothing answers open. */
    public val said: WhetherTheLocalNetworkIsOpen
        get() =
            when {
                !pathWasReadable -> WhetherTheLocalNetworkIsOpen.OPEN
                platformDeniedIt -> WhetherTheLocalNetworkIsOpen.FORBIDDEN
                else -> WhetherTheLocalNetworkIsOpen.OPEN
            }

    public companion object {
        /** A platform that let the path be taken. */
        public val PERMITTED: LocalNetworkRule = LocalNetworkRule(pathWasReadable = true, platformDeniedIt = false)

        /** A platform that said nothing, which answers open. */
        public val UNASKED: LocalNetworkRule = LocalNetworkRule(pathWasReadable = false, platformDeniedIt = false)
    }
}
