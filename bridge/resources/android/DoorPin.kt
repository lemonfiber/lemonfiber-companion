package app.lemonfiber.native

import java.security.MessageDigest

/**
 * The certificate the household's front door promised to present, and whether one presented is it.
 *
 * The door serves the household's library, and the core states its address
 * beside the fingerprint of the certificate it presents. Every byte the player
 * fetches — a manifest, a segment, a subtitle, a key — comes from a connection
 * whose leaf certificate hashes to that fingerprint, the same way the stack
 * itself is pinned. The platform's trust store is not asked and the hostname
 * is not read: the door is often an address on the house's own network, which
 * no public authority vouches for, and a pin is a stronger promise than either.
 *
 * **A fingerprint that is not one admits nothing.** Sixty-four hexadecimal
 * characters are a SHA-256 digest; anything else — short, long, a colon in
 * it, a stray space — is refused when the pin is made, so there is no pin
 * that a malformed answer from the core could turn into one that admits every
 * certificate.
 *
 * The comparison does not stop at the first difference, so how long it takes
 * says nothing about how much of a certificate matched.
 *
 * Deliberately mirrors `DoorPin.swift` line for line.
 */
public class DoorPin private constructor(
    /** The digest the presented leaf must hash to. */
    private val expected: ByteArray,
) {
    /**
     * Whether this is the certificate the door promised.
     *
     * @param leaf the presented leaf certificate, DER-encoded.
     * @return whether its digest is the pinned one.
     */
    public fun admits(leaf: ByteArray): Boolean =
        MessageDigest.isEqual(MessageDigest.getInstance(DIGEST).digest(leaf), expected)

    /** How a fingerprint is read into a pin. */
    public companion object {
        /** How many characters a fingerprint has: a SHA-256 digest, in hexadecimal. */
        public const val CHARACTERS: Int = 64

        /** The digest a fingerprint is of. */
        private const val DIGEST = "SHA-256"

        /** How many bits one hexadecimal character carries. */
        private const val NIBBLE = 4

        /** What a hexadecimal letter is worth above its place in the alphabet. */
        private const val TEN = 10

        /**
         * The pin for this fingerprint, or null where it is not one.
         *
         * Either case of hexadecimal is read, because a digest is the same digest
         * in both. Nothing else is forgiven.
         *
         * @param fingerprint the fingerprint the core stated for the door.
         * @return the pin, or null.
         */
        public fun of(fingerprint: String): DoorPin? {
            val nibbles = fingerprint.mapNotNull { nibble(it) }

            return if (fingerprint.length != CHARACTERS || nibbles.size != CHARACTERS) {
                null
            } else {
                DoorPin(
                    ByteArray(
                        CHARACTERS / 2,
                    ) { (nibbles[it * 2] shl NIBBLE or nibbles[it * 2 + 1]).toByte() },
                )
            }
        }

        /** The value of one hexadecimal character, or null where it is not one. */
        private fun nibble(character: Char): Int? =
            when (character) {
                in '0'..'9' -> character - '0'
                in 'a'..'f' -> character - 'a' + TEN
                in 'A'..'F' -> character - 'A' + TEN
                else -> null
            }
    }
}
