package app.lemonfiber.native

/**
 * Artwork a screen draws, held in memory for as long as the app runs and nowhere else.
 *
 * PHP fetches each picture through the SDK, pinned and on the session it is
 * drawn for, and hands the bytes here under an opaque key. An image element
 * whose source is `lemonfiber-art:<key>` is drawn from what is held. Nothing
 * here writes a file, reaches a disk cache or survives the process.
 *
 * Each picture is held for the stack it came from, so removing the stack or
 * ending its session lets go of that stack's pictures, and the lock lets go of
 * every one. A bound on the bytes held lets go of the oldest first.
 *
 * Deliberately mirrors `ArtworkHeld.swift`.
 */
public class ArtworkHeld(
    private val bound: Int,
) {
    private class One(
        val stack: String,
        val bytes: ByteArray,
    )

    private val held = LinkedHashMap<String, One>()

    private var total = 0

    /**
     * Hold a picture under a key for a stack, replacing what the key held.
     *
     * Answers whether it is held: a key that is not one, a stack named by
     * nothing, and bytes that are none or too many are refused.
     */
    @Synchronized
    public fun hold(
        key: String,
        stack: String,
        bytes: ByteArray,
    ): Boolean {
        if (!isAKey(key) || stack.isEmpty() || !isAPicture(bytes)) {
            return false
        }

        drop(key)
        held[key] = One(stack, bytes)
        total += bytes.size

        while (total > bound && held.isNotEmpty()) {
            drop(held.keys.first())
        }

        return held.containsKey(key)
    }

    /** The bytes held under a key, where they are. */
    @Synchronized
    public fun bytes(key: String): ByteArray? = held[key]?.bytes

    /** Whether a key's picture is held. */
    public fun holds(key: String): Boolean = bytes(key) != null

    /** Let go of every picture held for a stack, answering the keys let go of. */
    @Synchronized
    public fun letGoOf(stack: String): List<String> {
        val keys = held.filterValues { it.stack == stack }.keys.toList()
        keys.forEach(::drop)

        return keys
    }

    /** Let go of every picture held, answering the keys let go of. */
    @Synchronized
    public fun letGoOfEvery(): List<String> {
        val keys = held.keys.toList()
        held.clear()
        total = 0

        return keys
    }

    private fun drop(key: String) {
        val one = held.remove(key) ?: return

        total -= one.bytes.size
    }

    /** What a store is bounded by, and how a source and a picture are told apart from what is not one. */
    public companion object {
        /** What every key's source starts with. */
        public const val SCHEME: String = "lemonfiber-art:"

        /** The most bytes held at once. */
        public const val MOST_BYTES: Int = 48 * 1024 * 1024

        /** The most bytes one picture may be. */
        public const val MOST_BYTES_OF_ONE: Int = 4 * 1024 * 1024

        private val KEY = Regex("^lemonfiber-art:[0-9a-f]{16,64}$")

        /** The one store the app draws from. */
        public val shared: ArtworkHeld = ArtworkHeld(MOST_BYTES)

        /** Whether a source is one this store answers, carrying a key after the scheme. */
        public fun isAKey(source: String): Boolean = KEY.matches(source)

        private fun isAPicture(bytes: ByteArray): Boolean =
            bytes.isNotEmpty() && bytes.size <= MOST_BYTES_OF_ONE
    }
}
