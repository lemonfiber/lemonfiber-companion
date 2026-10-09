package app.lemonfiber.native

/**
 * An HLS playlist, read so that every address in it stays at the door.
 *
 * A playlist is a list of addresses: the renditions a master playlist offers,
 * the segments a media playlist plays, the key, the initialisation section and
 * the subtitles its tags name. The player follows every one of them, so every
 * one of them is read here first. Each is made absolute against the playlist's
 * own address and kept only where it is at the door; **one address off the
 * door refuses the whole playlist**, because a playlist that sends the player
 * anywhere else is not one the core stated, whatever the rest of it says.
 *
 * Where the platform's player has to be handed addresses under a private
 * scheme so that every fetch comes back through the pinned connection, the
 * addresses are written under that scheme. Where it reads them as they are,
 * the scheme stays `https` and the rule still refuses what is off the door.
 *
 * Lines are read whether they end in a line feed or a carriage return and a
 * line feed, and written with line feeds.
 *
 * Deliberately mirrors `PlaylistRule.swift` line for line.
 */
public class PlaylistRule(
    /** The door every address must be at. */
    public val door: Door,
    /** The scheme addresses are handed to the player under. */
    public val scheme: String,
) {
    /**
     * The playlist with every address made absolute and at the door, or null where one is not.
     *
     * @param playlist the playlist as the door served it.
     * @param address where it was served from.
     * @return the playlist as the player is to read it, or null.
     */
    public fun rewrite(
        playlist: String,
        address: String,
    ): String? {
        val written = mutableListOf<String>()

        for (raw in playlist.split("\n")) {
            val line = raw.removeSuffix("\r")

            written.add(rewriteLine(line, address) ?: return null)
        }

        return written.joinToString("\n")
    }

    /** One line, with its addresses rewritten, or null where one is off the door. */
    private fun rewriteLine(
        line: String,
        address: String,
    ): String? =
        when {
            line.isEmpty() -> line
            line.startsWith("#") -> rewriteAttributes(line, address)
            else -> handed(line, address)
        }

    /** A tag, with every `URI` attribute in it rewritten. */
    private fun rewriteAttributes(
        tag: String,
        address: String,
    ): String? {
        var rest = tag
        val written = StringBuilder()

        while (true) {
            val opens = rest.indexOf(ATTRIBUTE_OPENS)

            if (opens < 0) {
                break
            }

            written.append(rest, 0, opens + ATTRIBUTE_OPENS.length)
            rest = rest.substring(opens + ATTRIBUTE_OPENS.length)

            val closes = rest.indexOf(ATTRIBUTE_CLOSES)
            val kept = if (closes < 0) null else handed(rest.substring(0, closes), address)

            written.append(kept ?: return null)
            rest = rest.substring(closes)
        }

        return written.append(rest).toString()
    }

    /** One address, absolute and under the player's scheme, or null where it is off the door. */
    private fun handed(
        reference: String,
        address: String,
    ): String? {
        val absolute = door.resolve(reference, address) ?: return null

        return scheme + absolute.substring(Door.SCHEME.length)
    }

    private companion object {
        /** What opens an address inside a tag. */
        const val ATTRIBUTE_OPENS = "URI=\""

        /** What closes it. */
        const val ATTRIBUTE_CLOSES = '"'
    }
}
