/// Why playback stopped, as one closed word.
///
/// Deliberately mirrors `PlaybackRule.kt` line for line.
public enum WhyPlaybackStopped: String, Sendable {
    /// The door did not answer, or stopped answering: the library is out of reach from here.
    case unreachable = "unreachable"

    /// The door presented a certificate other than the one the core stated.
    case pinMismatch = "pin_mismatch"

    /// What the door served is not something this device can play.
    case unsupportedFormat = "unsupported_format"

    /// The door answered and would not serve it, or served something the player will not follow.
    case refused = "refused"

    /// What this answer is called on the wire.
    public var word: String { rawValue }
}

/// When the player reports how far it got, and when it stops waiting.
///
/// **Playback that cannot continue stops and says why; it never waits on a
/// spinner.** A stream that stalls is given as long as a platform's own
/// rebuffering reasonably takes, and no longer: past that the library is out of
/// reach from here, and the honest answer is to stop and say so rather than to
/// leave a member watching a wheel that will not resolve.
///
/// **Progress is reported often enough to resume from, and never queued.** A
/// report goes every ten seconds while playing and at once on a pause, a seek,
/// the end or a close. A report that cannot be sent is dropped — the next one
/// says the same thing, later and more accurately — so nothing is ever held to
/// be sent at a moment nobody chose.
///
/// Deliberately mirrors `PlaybackRule.kt` line for line.
public enum PlaybackRule {
    /// How often a position is reported while playing, in seconds.
    public static let reportEvery: Double = 10

    /// How long a stall is waited out before playback stops, in seconds.
    public static let patienceWhileStalled: Double = 10

    /// The statuses a door refuses a stream with.
    private static let refusals: Set<Int> = [401, 403, 404, 410]

    /// The status a door answers a format it will not serve with.
    private static let unservable = 415

    /// Whether to report the position now.
    ///
    /// - Parameters:
    ///   - sinceLastReport: seconds since the last report.
    ///   - isPlaying: whether the picture is moving.
    ///   - moved: whether the viewer paused, sought, closed or reached the end.
    /// - Returns: whether to report.
    public static func reportsProgress(sinceLastReport: Double, isPlaying: Bool, moved: Bool) -> Bool {
        moved || (isPlaying && sinceLastReport >= reportEvery)
    }

    /// Whether a stall has gone on long enough to stop.
    ///
    /// - Parameter stalledFor: seconds since the picture stopped for want of data.
    /// - Returns: whether to stop and say the library is out of reach.
    public static func givesUp(stalledFor: Double) -> Bool {
        stalledFor >= patienceWhileStalled
    }

    /// Why a door's answer stopped playback.
    ///
    /// - Parameter status: the status the door answered with.
    /// - Returns: the closed word for why.
    public static func why(status: Int) -> WhyPlaybackStopped {
        if refusals.contains(status) {
            return .refused
        }

        return status == unservable ? .unsupportedFormat : .unreachable
    }
}
