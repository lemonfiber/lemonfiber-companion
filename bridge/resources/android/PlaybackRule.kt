package app.lemonfiber.native

/**
 * Why playback stopped, as one closed word.
 *
 * Deliberately mirrors `PlaybackRule.swift` line for line.
 */
public enum class WhyPlaybackStopped(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** The door did not answer, or stopped answering: the library is out of reach from here. */
    UNREACHABLE("unreachable"),

    /** The door presented a certificate other than the one the core stated. */
    PIN_MISMATCH("pin_mismatch"),

    /** What the door served is not something this device can play. */
    UNSUPPORTED_FORMAT("unsupported_format"),

    /** The door answered and would not serve it, or served something the player will not follow. */
    REFUSED("refused"),
}

/**
 * When the player reports how far it got, and when it stops waiting.
 *
 * **Playback that cannot continue stops and says why; it never waits on a
 * spinner.** A stream that stalls is given as long as a platform's own
 * rebuffering reasonably takes, and no longer: past that the library is out of
 * reach from here, and the honest answer is to stop and say so rather than to
 * leave a member watching a wheel that will not resolve.
 *
 * **Progress is reported often enough to resume from, and never queued.** A
 * report goes every ten seconds while playing and at once on a pause, a seek,
 * the end or a close. A report that cannot be sent is dropped — the next one
 * says the same thing, later and more accurately — so nothing is ever held to
 * be sent at a moment nobody chose.
 *
 * Deliberately mirrors `PlaybackRule.swift` line for line.
 */
public object PlaybackRule {
    /** How often a position is reported while playing, in seconds. */
    public const val REPORT_EVERY: Double = 10.0

    /** How long a stall is waited out before playback stops, in seconds. */
    public const val PATIENCE_WHILE_STALLED: Double = 10.0

    /** The status a door answers a request with no usable grant with. */
    private const val UNAUTHORISED = 401

    /** The status a door answers a grant that may not see this title with. */
    private const val FORBIDDEN = 403

    /** The status a door answers a title it does not hold with. */
    private const val NOT_FOUND = 404

    /** The status a door answers a title that has gone with. */
    private const val GONE = 410

    /** The statuses a door refuses a stream with. */
    private val REFUSALS = setOf(UNAUTHORISED, FORBIDDEN, NOT_FOUND, GONE)

    /** The status a door answers a format it will not serve with. */
    private const val UNSERVABLE = 415

    /**
     * Whether to report the position now.
     *
     * @param sinceLastReport seconds since the last report.
     * @param isPlaying whether the picture is moving.
     * @param moved whether the viewer paused, sought, closed or reached the end.
     * @return whether to report.
     */
    public fun reportsProgress(
        sinceLastReport: Double,
        isPlaying: Boolean,
        moved: Boolean,
    ): Boolean = moved || (isPlaying && sinceLastReport >= REPORT_EVERY)

    /**
     * Whether a stall has gone on long enough to stop.
     *
     * @param stalledFor seconds since the picture stopped for want of data.
     * @return whether to stop and say the library is out of reach.
     */
    public fun givesUp(stalledFor: Double): Boolean = stalledFor >= PATIENCE_WHILE_STALLED

    /**
     * Why a door's answer stopped playback.
     *
     * @param status the status the door answered with.
     * @return the closed word for why.
     */
    public fun why(status: Int): WhyPlaybackStopped {
        if (status in REFUSALS) {
            return WhyPlaybackStopped.REFUSED
        }

        return if (status == UNSERVABLE) {
            WhyPlaybackStopped.UNSUPPORTED_FORMAT
        } else {
            WhyPlaybackStopped.UNREACHABLE
        }
    }
}
