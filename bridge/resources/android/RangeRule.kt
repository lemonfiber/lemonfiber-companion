package app.lemonfiber.native

/**
 * Byte ranges, as the player asks for part of a file and checks what came back.
 *
 * Direct play reads a file from wherever the viewer seeks to, so the player
 * asks the door for a range rather than the whole. What comes back is checked
 * against what was asked: a door that answers a range with the whole file
 * would hand the player the wrong bytes at the right position, and a picture
 * that plays the wrong second is worse than one that stops.
 *
 * Deliberately mirrors `RangeRule.swift` line for line.
 */
public object RangeRule {
    /** The status a whole file is answered with. */
    public const val WHOLE: Int = 200

    /** The status part of one is answered with. */
    public const val PART: Int = 206

    /**
     * The `Range` header for a read, or null where the read is the whole file.
     *
     * @param offset the first byte wanted.
     * @param length how many bytes, or null for everything after the first.
     * @return the header's value, or null.
     */
    public fun asked(
        offset: Long,
        length: Long?,
    ): String? {
        if (length == null) {
            return if (offset == 0L) null else "bytes=$offset-"
        }

        return "bytes=$offset-${offset + length - 1}"
    }

    /**
     * The whole file's length, read off a `Content-Range` header, or null where it does not say.
     *
     * @param contentRange the header's value, if there was one.
     * @return the length, or null.
     */
    public fun total(contentRange: String?): Long? {
        val slash = contentRange?.lastIndexOf('/') ?: return null

        return if (slash < 0) null else contentRange.substring(slash + 1).toLongOrNull()
    }

    /**
     * Whether an answer is the one asked for.
     *
     * @param status the status the door answered with.
     * @param askedForPart whether a `Range` header was sent.
     * @return whether the bytes that came back are the bytes wanted.
     */
    public fun admits(
        status: Int,
        askedForPart: Boolean,
    ): Boolean = status == (if (askedForPart) PART else WHOLE)
}
