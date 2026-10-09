package app.lemonfiber.native

/**
 * Where playback stands, as one closed word.
 *
 * Deliberately mirrors `PlayerState.swift` line for line.
 */
public enum class WherePlaybackStands(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** The player is open and the stream is not yet ready. */
    OPENING("opening"),

    /** The picture is moving. */
    PLAYING("playing"),

    /** The viewer paused it. */
    PAUSED("paused"),

    /** The picture stopped for want of data, and is being waited out. */
    STALLED("stalled"),

    /** It played to the end. */
    ENDED("ended"),

    /** It stopped and could not go on; the state says why. */
    STOPPED("stopped"),

    /** No player is open. */
    CLOSED("closed"),
}

/**
 * What the player says when the app asks where it is.
 *
 * **The only way anything about playback reaches the app.** What the player
 * sends on its own carries nothing — it says only *ask again* — so a forged
 * or replayed event can make the app ask, and nothing more. This is the
 * answer to that asking: where playback stands, how far it got, every track
 * on offer and which one is chosen, and why it stopped where it did.
 *
 * Nothing in it is the member's, and nothing in it is the door's: no
 * address, no grant, no fingerprint. A track is its id, its language and its
 * label.
 *
 * Deliberately mirrors `PlayerState.swift` line for line.
 */
public data class PlayerState(
    /** Where playback stands. */
    public val stands: WherePlaybackStands,
    /** How far it got, in seconds. */
    public val position: Double,
    /** How long it is, in seconds, or zero before the stream says. */
    public val duration: Double,
    /** Every sound track on offer. */
    public val audio: List<OfferedTrack>,
    /** Every subtitle track on offer. */
    public val subtitles: List<OfferedTrack>,
    /** The sound track playing, or null. */
    public val chosenAudio: String?,
    /** The subtitles shown, or null for none. */
    public val chosenSubtitle: String?,
    /** Why it stopped, where it did. */
    public val why: WhyPlaybackStopped?,
) {
    /**
     * The state as the bridge carries it.
     *
     * @return closed words and numbers, with nothing chosen written as empty.
     */
    public fun answer(): Map<String, Any> =
        mapOf(
            "stands" to stands.word,
            "position" to position,
            "duration" to duration,
            "audio" to audio.map(::track),
            "subtitles" to subtitles.map(::track),
            "chosen_audio" to (chosenAudio ?: ""),
            "chosen_subtitle" to (chosenSubtitle ?: ""),
            "why" to (why?.word ?: ""),
        )

    /** The state no player is in. */
    public companion object {
        /** No player open. */
        public val CLOSED: PlayerState =
            PlayerState(WherePlaybackStands.CLOSED, 0.0, 0.0, emptyList(), emptyList(), null, null, null)

        /** One track as the bridge carries it. */
        private fun track(track: OfferedTrack): Map<String, Any> =
            mapOf("id" to track.id, "language" to track.language, "label" to track.label)
    }
}
