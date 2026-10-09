package app.lemonfiber.native

/**
 * A sound or subtitle track the stream offers.
 *
 * Deliberately mirrors `TrackRule.swift` line for line.
 */
public data class OfferedTrack(
    /** What the player calls it. */
    public val id: String,
    /** Its language, as a tag such as `en` or `nl-BE`, or empty where the stream does not say. */
    public val language: String,
    /** What the stream labels it. */
    public val label: String,
    /** Whether the stream marks it as the one to play unless asked otherwise. */
    public val isDefault: Boolean,
)

/**
 * Which sound and which subtitles to start with.
 *
 * The member's preference first, matched on the language and not on the
 * region, so a preference for English plays the British track where that is
 * the only English one. **Sound always plays**: without a match it is the
 * stream's own default, and without one of those the first. **Subtitles are
 * never forced on**: without a match there are none, because subtitles in a
 * language the member did not ask for are a screen covered in words they
 * cannot read.
 *
 * Deliberately mirrors `TrackRule.swift` line for line.
 */
public object TrackRule {
    /** The member's word for no subtitles. */
    public const val OFF: String = "off"

    /**
     * The sound to start with, or null where the stream offers none.
     *
     * @param offered every sound track the stream offers.
     * @param preferred the language the member prefers, or empty.
     * @return the track's id, or null.
     */
    public fun audio(
        offered: List<OfferedTrack>,
        preferred: String,
    ): String? {
        val chosen =
            matching(offered, preferred)
                ?: offered.firstOrNull { it.isDefault }
                ?: offered.firstOrNull()

        return chosen?.id
    }

    /**
     * The subtitles to start with, or null for none.
     *
     * @param offered every subtitle track the stream offers.
     * @param preferred the language the member prefers, empty or `off` for none.
     * @return the track's id, or null.
     */
    public fun subtitle(
        offered: List<OfferedTrack>,
        preferred: String,
    ): String? = if (preferred == OFF) null else matching(offered, preferred)?.id

    /** The first track in the preferred language, or null. */
    private fun matching(
        offered: List<OfferedTrack>,
        preferred: String,
    ): OfferedTrack? {
        val wanted = primary(preferred)

        if (wanted.isEmpty()) {
            return null
        }

        return offered.firstOrNull { primary(it.language) == wanted }
    }

    /** A language tag's language, without its region or script, in lower case. */
    private fun primary(tag: String): String = tag.takeWhile { it != '-' && it != '_' }.lowercase()
}
