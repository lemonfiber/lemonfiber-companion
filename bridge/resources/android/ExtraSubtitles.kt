package app.lemonfiber.native

/**
 * Subtitles an extension offers beside a stream, joined into the stream's own master playlist.
 *
 * A subtitle file at the door that the stream does not list — one an
 * extension found, or one a member added — has no place in an HLS stream
 * until a playlist names it. So each is joined to the stream's master
 * playlist as a subtitle rendition, under a private scheme whose playlists
 * are written here rather than fetched: one segment, the file itself, said to
 * last as long as anything plays. The file is fetched like every other byte,
 * through the door and over the pin.
 *
 * Only a master playlist is joined; a media playlist or a file played directly
 * is handed back as it was. Each rendition joins the subtitle group the
 * stream's variants already name, or a group of its own that every variant is
 * given. A label is stripped of what a quoted value cannot hold, and a
 * language is written only where it is a plain tag.
 *
 * On Android the platform's player takes a subtitle file beside a stream as
 * it is, so nothing there asks this; it is mirrored so both halves state the
 * same rule.
 *
 * Deliberately mirrors `ExtraSubtitles.swift` line for line.
 */
public class ExtraSubtitles(
    offered: List<ExtraTrack>,
) {
    /** The subtitle tracks to join, in the order they were offered. */
    public val tracks: List<ExtraTrack> = offered.filter { it.kind == KindOfTrack.SUBTITLE }

    /**
     * The track a handed address names, or null where it names none.
     *
     * @param address an address the player asked for.
     * @return the track, or null.
     */
    public fun track(address: String): ExtraTrack? =
        address
            .takeIf { it.startsWith(OPENS) }
            ?.drop(OPENS.length)
            ?.takeIf { it.isNotEmpty() && it.all { c -> c in '0'..'9' } }
            ?.toIntOrNull()
            ?.let { tracks.getOrNull(it) }

    /**
     * The playlist with every track joined as a subtitle rendition, where it is a master playlist.
     *
     * @param playlist a playlist already read through [PlaylistRule].
     * @return the playlist with the renditions joined, or as it was.
     */
    public fun join(playlist: String): String {
        val lines = playlist.split("\n")
        val isAMaster = tracks.isNotEmpty() && lines.first() == "#EXTM3U" && lines.any { isAVariant(it) }
        val group = lines.firstNotNullOfOrNull { subtitleGroup(it) } ?: GROUP
        val renditions = tracks.indices.map { rendition(it, group) }
        val variants =
            lines.drop(1).map { line ->
                if (isAVariant(line) && subtitleGroup(line) == null) "$line,SUBTITLES=\"$group\"" else line
            }

        return if (isAMaster) (listOf(lines[0]) + renditions + variants).joinToString("\n") else playlist
    }

    /** One track as a subtitle rendition in a group. */
    private fun rendition(
        index: Int,
        group: String,
    ): String {
        val track = tracks[index]
        val label = track.label.filter { isQuotable(it) }
        val name = label.ifEmpty { "Subtitles ${index + 1}" }
        val language =
            if (track.language.isNotEmpty() && track.language.all { isATagCharacter(it) }) {
                ",LANGUAGE=\"${track.language}\""
            } else {
                ""
            }

        return "#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID=\"$group\",NAME=\"$name\"$language" +
            ",AUTOSELECT=NO,DEFAULT=NO,URI=\"${address(index)}\""
    }

    /** How addresses and playlists are written. */
    public companion object {
        /** The scheme a joined rendition's playlist is handed to the player under. */
        public const val SCHEME: String = "lfextra"

        /** The subtitle group joined renditions form where the stream names none. */
        internal const val GROUP = "lf-extra"

        /** How long a joined subtitle file is said to last, in seconds: longer than anything plays. */
        internal const val LASTING = 86_400

        /** What opens the address of a joined rendition's playlist. */
        private const val OPENS = "$SCHEME://track/"

        /** The lowest character a quoted value can hold. */
        private const val FIRST_VISIBLE = ' '

        /**
         * The address the player is handed for one joined rendition's playlist.
         *
         * @param index the track's place among the tracks joined.
         * @return the address.
         */
        public fun address(index: Int): String = OPENS + index

        /**
         * The media playlist that plays one subtitle file for as long as anything plays.
         *
         * @param address the file, as the player is handed it.
         * @return the playlist.
         */
        public fun playlist(address: String): String =
            listOf(
                "#EXTM3U",
                "#EXT-X-VERSION:3",
                "#EXT-X-TARGETDURATION:$LASTING",
                "#EXT-X-MEDIA-SEQUENCE:0",
                "#EXT-X-PLAYLIST-TYPE:VOD",
                "#EXTINF:$LASTING,",
                address,
                "#EXT-X-ENDLIST",
                "",
            ).joinToString("\n")

        /** Whether a quoted value can hold a character: not a quote, and not a control like a line break. */
        private fun isQuotable(character: Char): Boolean = character != '"' && character >= FIRST_VISIBLE

        /** Whether a character belongs in a plain language tag. */
        private fun isATagCharacter(character: Char): Boolean =
            character in 'a'..'z' || character in 'A'..'Z' || character in '0'..'9' || character == '-'

        /** Whether a line names a variant of the stream. */
        private fun isAVariant(line: String): Boolean = line.uppercase().startsWith("#EXT-X-STREAM-INF:")

        /** The subtitle group a variant names, or null. */
        private fun subtitleGroup(line: String): String? {
            val attributes = if (isAVariant(line)) PlaylistRule.split(line.substringAfter(':')) else null

            return attributes?.firstNotNullOfOrNull { attribute ->
                val parts = attribute.split("=", limit = 2)
                val value = parts.getOrNull(1).orEmpty()
                val quoted = value.length >= 2 && value.startsWith('"') && value.endsWith('"')

                if (parts[0].uppercase() == "SUBTITLES" && quoted) {
                    value.substring(
                        1,
                        value.length - 1,
                    )
                } else {
                    null
                }
            }
        }
    }
}
