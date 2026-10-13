package app.lemonfiber.native

import java.net.URI
import java.net.URISyntaxException

/**
 * Where a tap on a notification may lead: back into this app, and nowhere else.
 *
 * The link a notification is handed is opened by the platform, so a link under
 * any other scheme would be a notification that opens somebody else's app or a
 * web page. Only a link under the scheme this app is opened at, naming a screen,
 * is kept; anything else leaves the notification leading nowhere.
 *
 * No Android class in sight, so it runs on a JVM with no device.
 *
 * Deliberately mirrors `TapRule.swift` line for line.
 */
public object TapRule {
    /** The scheme this app is opened at, which every join link is written under too. */
    public const val SCHEME: String = "lemonfiber"

    /** The link, where it leads into this app; null where it leads anywhere else or is no link at all. */
    public fun opens(said: String?): String? {
        val link =
            try {
                said?.let(::URI)
            } catch (_: URISyntaxException) {
                null
            }

        val leadsIn =
            link != null &&
                link.scheme == SCHEME &&
                !link.host.isNullOrEmpty() &&
                link.userInfo == null &&
                link.port == -1

        return if (leadsIn) said else null
    }
}
