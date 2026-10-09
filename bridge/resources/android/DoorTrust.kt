package app.lemonfiber.native

/**
 * Whether a connection the player opened reached the door the core stated.
 *
 * Two things together, and neither alone: the connection is to the door's
 * host and port, and the certificate it presented is the one pinned. A right
 * certificate somewhere else is a copy of the door's key on another machine;
 * the right machine presenting another certificate is not the door the core
 * vouched for. **A connection that presented no certificate is refused**,
 * because there is nothing to compare and nothing to trust.
 *
 * The platform's own trust evaluation is not asked. What it would say about a
 * certificate on a house's own network is not the question; whether this is
 * the certificate the core stated is.
 *
 * Deliberately mirrors `DoorTrust.swift` line for line.
 */
public class DoorTrust(
    /** The door the connection must be to. */
    public val door: Door,
    /** The certificate it must present. */
    public val pin: DoorPin,
) {
    /**
     * Whether a connection is to the door and presented its certificate.
     *
     * @param leaf the leaf certificate the connection presented, DER-encoded, or null for none.
     * @param host the host the connection is to.
     * @param port the port it is to.
     * @return whether to trust it.
     */
    public fun admits(
        leaf: ByteArray?,
        host: String,
        port: Int,
    ): Boolean = leaf != null && host.lowercase() == door.host && port == door.port && pin.admits(leaf)
}
