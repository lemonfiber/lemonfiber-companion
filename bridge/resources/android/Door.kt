package app.lemonfiber.native

import java.net.URI
import java.net.URISyntaxException

/**
 * The household's front door, as the origin every request the player makes must stay at.
 *
 * The core states where a title streams from, and that location names the
 * door: its scheme, its host and its port. The player asks nothing of any
 * other origin. A redirect elsewhere, a playlist naming a segment on another
 * machine, a subtitle on a public server: each is refused rather than
 * followed, because a request off the door is a request the pin does not
 * cover and the core did not state.
 *
 * **Only `https`, and never with a name or password in the address.** A door
 * reached in the clear has nothing to pin, and credentials written into an
 * address end up in logs and caches; the grant travels in a header instead.
 *
 * Deliberately mirrors `Door.swift` line for line.
 */
@ConsistentCopyVisibility
public data class Door private constructor(
    /** The door's host, in lower case. */
    public val host: String,
    /** The door's port, with the default written out. */
    public val port: Int,
) {
    /**
     * Whether an address is at this door.
     *
     * @param address an absolute address.
     * @return whether its scheme, host and port are the door's.
     */
    public fun holds(address: String): Boolean = admitted(address) != null

    /**
     * An address at this door, parsed as the platform fetches it, or null where it is not at the door.
     *
     * What is fetched is the very address that was checked: the caller hands
     * the platform this, never the text it came from parsed a second time.
     *
     * @param address an absolute address.
     * @return the address, or null.
     */
    public fun admitted(address: String): URI? = parsed(address)?.takeIf { it.first == this }?.second

    /**
     * An address a document at the door names, made absolute, or null where it is not at the door.
     *
     * @param reference the address as the document wrote it, relative or absolute.
     * @param base the address of the document that named it.
     * @return the absolute address, or null.
     */
    public fun resolve(
        reference: String,
        base: String,
    ): String? {
        val resolved = uri(base)?.let { from -> uri(reference)?.let { from.resolve(it).toString() } }

        return resolved?.takeIf { holds(it) }
    }

    /** How a location is read into a door. */
    public companion object {
        /** The one scheme a door is reached over. */
        public const val SCHEME: String = "https"

        /** The port `https` means where an address names none. */
        private const val DEFAULT_PORT = 443

        /** What `URI` answers for a port the address does not name. */
        private const val NO_PORT = -1

        /** The highest port a connection can be made to. */
        private const val HIGHEST_PORT = 65_535

        /** The characters an address may hold at all: visible ASCII. */
        private val VISIBLE = '!'..'~'

        /** What a separator after `//` begins: the path, the query or the fragment. */
        private const val AUTHORITY_ENDS = "/?#"

        /**
         * The door a location the core stated names, or null where it names none.
         *
         * @param location where the core said a title streams from.
         * @return the door, or null.
         */
        public fun of(location: String): Door? = parsed(location)?.first

        /**
         * An address read twice, by this rule and by the platform, and kept only where the two agree.
         *
         * Two readers of one address are two chances to disagree about where it
         * goes, and a disagreement is how a check passes on one host while the
         * fetch goes to another. So the authority is read here by a grammar with
         * no corners — `https://`, a host of plain letters, digits, hyphens and
         * dots, and an optional port written as a plain number — and the
         * platform's reading must name the same host and port with no name or
         * password. Everything the grammar has no place for is refused rather
         * than interpreted: a name or password before the host, a percent escape
         * or a letter outside ASCII in it, a trailing dot, a bare IPv6 address, a
         * backslash, a space or a control character anywhere.
         */
        private fun parsed(address: String): Pair<Door, URI>? {
            val written = authority(address) ?: return null

            return uri(address)
                ?.takeIf { agrees(it, written) }
                ?.let { Pair(Door(written.first, written.second ?: DEFAULT_PORT), it) }
        }

        /** An address as the platform reads it, or null where it cannot. */
        private fun uri(text: String): URI? =
            try {
                URI(text)
            } catch (_: URISyntaxException) {
                null
            }

        /** Whether the platform's reading names the host and port the grammar read, and nothing else. */
        internal fun agrees(
            read: URI,
            written: Pair<String, Int?>,
        ): Boolean =
            read.scheme?.lowercase() == SCHEME &&
                read.rawUserInfo == null &&
                read.host?.lowercase() == written.first &&
                read.port == (written.second ?: NO_PORT)

        /** The host and port an address names, by the grammar above, or null where it falls outside it. */
        internal fun authority(address: String): Pair<String, Int?>? {
            val visible = address.all { it in VISIBLE && it != '\\' }
            val clean = visible && address.lowercase().startsWith("$SCHEME://")
            val named = address.drop("$SCHEME://".length).takeWhile { it !in AUTHORITY_ENDS }.lowercase()
            val parts = named.split(":")
            val port = parts.getOrNull(1)?.let(::port)
            val portHolds = parts.size == 1 || port != null

            return Pair(parts[0], port).takeIf { clean && parts.size <= 2 && isAHost(parts[0]) && portHolds }
        }

        /** Whether a name is dot-separated labels of letters, digits and inner hyphens. */
        private fun isAHost(name: String): Boolean =
            name.split(".").all { label ->
                label.isNotEmpty() &&
                    label.first() != '-' &&
                    label.last() != '-' &&
                    label.all { it in 'a'..'z' || it in '0'..'9' || it == '-' }
            }

        /** A port written as a plain number a connection can use, or null. */
        private fun port(written: String): Int? =
            written
                .takeIf { !it.startsWith("0") && it.all { c -> c in '0'..'9' } }
                ?.toIntOrNull()
                ?.takeIf { it in 1..HIGHEST_PORT }
    }
}
