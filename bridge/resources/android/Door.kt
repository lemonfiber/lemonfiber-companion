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
    public fun holds(address: String): Boolean = of(address) == this

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
        val resolved =
            try {
                URI(base).resolve(URI(reference)).toString()
            } catch (_: URISyntaxException) {
                null
            }

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

        /**
         * The door a location the core stated names, or null where it names none.
         *
         * @param location where the core said a title streams from.
         * @return the door, or null.
         */
        public fun of(location: String): Door? {
            val parts =
                try {
                    URI(location)
                } catch (_: URISyntaxException) {
                    null
                }

            return parts
                ?.takeIf { isADoor(it) }
                ?.let { Door(it.host.lowercase(), if (it.port == NO_PORT) DEFAULT_PORT else it.port) }
        }

        /** Whether a parsed address is an `https` one with a host and no credentials. */
        private fun isADoor(parts: URI): Boolean =
            parts.scheme?.lowercase() == SCHEME && parts.rawUserInfo == null && !parts.host.isNullOrEmpty()
    }
}
