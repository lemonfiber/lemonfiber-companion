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
 * **Read the way the player reads it, and closed where that is not certain.**
 * A tag's attributes are split at the commas outside quoted values, so a
 * quoted value holding a comma, or holding the letters `URI="`, is one value
 * of one attribute. Every attribute whose name ends in `URI`, and the
 * interstitials' `X-ASSET-LIST`, is an address and must be a quoted string.
 * Any other value, and any other part of a tag, that reads as an absolute
 * address — a scheme, `://` anywhere, or `//` — refuses the playlist, because the player
 * fetches an absolute address by itself. A relative one in a value this rule
 * does not know is harmless: the player resolves it against the playlist's
 * own address, which is under the private scheme. A playlist that defines
 * variables is refused, because the player substitutes them into addresses
 * this rule never sees whole. Comments, which the player ignores, are dropped,
 * and tags are recognised whatever case they are written in.
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
    /** The tags a playlist is refused for: they define variables. */
    private val refusedTags = setOf("EXT-X-DEFINE")

    /** The attributes that name an address without ending in `URI`. */
    private val addressAttributes = setOf("X-ASSET-LIST")

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
        val written = playlist.split("\n").map { rewriteLine(it.removeSuffix("\r"), address) }

        return written
            .takeUnless { playlist.contains(VARIABLE) || it.contains(null) }
            ?.flatMap { it.orEmpty() }
            ?.joinToString("\n")
    }

    /**
     * One line as the player is to read it: itself rewritten, nothing for a comment, or null where it is
     * refused.
     */
    private fun rewriteLine(
        line: String,
        address: String,
    ): List<String>? =
        when {
            line.isEmpty() -> listOf(line)
            !line.startsWith("#") -> handed(line.trim(' ', '\t'), address)?.let { listOf(it) }
            line.uppercase().startsWith(TAG_OPENS) -> rewriteTag(line, address)?.let { listOf(it) }
            else -> emptyList()
        }

    /** A tag, with every address among its attributes rewritten, or null where one cannot be. */
    private fun rewriteTag(
        tag: String,
        address: String,
    ): String? {
        val colon = tag.indexOf(':')
        val name = if (colon < 0) "" else tag.substring(1, colon)
        val written =
            if (colon < 0) {
                null
            } else {
                split(
                    tag.substring(colon + 1),
                )?.map { rewriteAttribute(it, address) }
            }

        return when {
            colon < 0 -> tag
            name.uppercase() in refusedTags || written == null || written.contains(null) -> null
            else -> tag.substring(0, colon + 1) + written.joinToString(",")
        }
    }

    /** One attribute, its address rewritten where it names one, or null where it is refused. */
    private fun rewriteAttribute(
        attribute: String,
        address: String,
    ): String? {
        val equals = attribute.indexOf('=')
        val bare = equals < 0 || attribute.substring(0, equals).contains('"')
        val name = if (bare) "" else attribute.substring(0, equals)
        val value = if (bare) attribute else attribute.substring(equals + 1)
        val upper = name.uppercase()
        val namesAnAddress = !bare && (upper.endsWith("URI") || upper in addressAttributes)

        return if (namesAnAddress) {
            rewriteAddress(name, value, address)
        } else {
            attribute.takeUnless { readsAsAnAddress(value) }
        }
    }

    /** An attribute that names an address: a quoted string, rewritten, or null. */
    private fun rewriteAddress(
        name: String,
        value: String,
        address: String,
    ): String? {
        val quoted = value.length >= 2 && value.first() == '"' && value.last() == '"'

        return value
            .takeIf { quoted }
            ?.let { handed(it.substring(1, it.length - 1), address) }
            ?.let { "$name=\"$it\"" }
    }

    /** One address, absolute and under the player's scheme, or null where it is off the door. */
    private fun handed(
        reference: String,
        address: String,
    ): String? {
        val absolute = door.resolve(reference, address)?.takeIf { reference.isNotEmpty() } ?: return null

        return scheme + absolute.substring(Door.SCHEME.length)
    }

    internal companion object {
        /** What marks a variable in use, in a line or a value. */
        const val VARIABLE = "{$"

        /** What every tag begins with, in any case. */
        const val TAG_OPENS = "#EXT"

        /** The characters a scheme is written in after its first letter. */
        const val SCHEME_MARKS = "+.-"

        /** A tag's value split at the commas outside quoted strings, or null where a quote is left open. */
        fun split(value: String): List<String>? {
            val attributes = mutableListOf<String>()
            val current = StringBuilder()
            var quoted = false

            for (character in value) {
                if (character == ',' && !quoted) {
                    attributes.add(current.toString())
                    current.clear()
                } else {
                    quoted = if (character == '"') !quoted else quoted
                    current.append(character)
                }
            }

            return (attributes + current.toString()).takeUnless { quoted }
        }

        /**
         * Whether a value the player does not take as an address could still be fetched as one:
         * it names a scheme, holds `://` anywhere, or begins with `//`.
         */
        fun readsAsAnAddress(value: String): Boolean {
            val bare = value.trim(' ', '\t', '"')
            val scheme = bare.takeWhile { it.isAsciiLetterOrDigit() || it in SCHEME_MARKS }

            return bare.contains("://") ||
                bare.startsWith("//") ||
                bare.startsWith("\\\\") ||
                (bare.firstOrNull()?.isLetter() == true && bare.drop(scheme.length).startsWith(":"))
        }

        private fun Char.isAsciiLetterOrDigit(): Boolean =
            this in 'a'..'z' || this in 'A'..'Z' || this in '0'..'9'
    }
}
