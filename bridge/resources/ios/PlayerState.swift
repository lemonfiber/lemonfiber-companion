/// Where playback stands, as one closed word.
///
/// Deliberately mirrors `PlayerState.kt` line for line.
public enum WherePlaybackStands: String, Sendable {
    /// The player is open and the stream is not yet ready.
    case opening = "opening"

    /// The picture is moving.
    case playing = "playing"

    /// The viewer paused it.
    case paused = "paused"

    /// The picture stopped for want of data, and is being waited out.
    case stalled = "stalled"

    /// It played to the end.
    case ended = "ended"

    /// It stopped and could not go on; the state says why.
    case stopped = "stopped"

    /// No player is open.
    case closed = "closed"

    /// What this answer is called on the wire.
    public var word: String { rawValue }
}

/// What the player says when the app asks where it is.
///
/// **The only way anything about playback reaches the app.** What the player
/// sends on its own carries nothing — it says only *ask again* — so a forged
/// or replayed event can make the app ask, and nothing more. This is the
/// answer to that asking: where playback stands, how far it got, every track
/// on offer and which one is chosen, and why it stopped where it did.
///
/// Nothing in it is the member's, and nothing in it is the door's: no
/// address, no grant, no fingerprint. A track is its id, its language and its
/// label.
///
/// Deliberately mirrors `PlayerState.kt` line for line.
public struct PlayerState: Equatable, Sendable {
    /// Where playback stands.
    public let stands: WherePlaybackStands

    /// How far it got, in seconds.
    public let position: Double

    /// How long it is, in seconds, or zero before the stream says.
    public let duration: Double

    /// Every sound track on offer.
    public let audio: [OfferedTrack]

    /// Every subtitle track on offer.
    public let subtitles: [OfferedTrack]

    /// The sound track playing, or nil.
    public let chosenAudio: String?

    /// The subtitles shown, or nil for none.
    public let chosenSubtitle: String?

    /// Why it stopped, where it did.
    public let why: WhyPlaybackStopped?

    /// A state as the player reads it.
    public init(
        stands: WherePlaybackStands, position: Double, duration: Double, audio: [OfferedTrack],
        subtitles: [OfferedTrack], chosenAudio: String?, chosenSubtitle: String?, why: WhyPlaybackStopped?
    ) {
        self.stands = stands
        self.position = position
        self.duration = duration
        self.audio = audio
        self.subtitles = subtitles
        self.chosenAudio = chosenAudio
        self.chosenSubtitle = chosenSubtitle
        self.why = why
    }

    /// No player open.
    public static let closed = PlayerState(
        stands: .closed, position: 0, duration: 0, audio: [], subtitles: [], chosenAudio: nil,
        chosenSubtitle: nil, why: nil)

    /// The state as the bridge carries it.
    ///
    /// - Returns: closed words and numbers, with nothing chosen written as empty.
    public func answer() -> [String: Any] {
        [
            "stands": stands.word,
            "position": position,
            "duration": duration,
            "audio": audio.map(Self.track),
            "subtitles": subtitles.map(Self.track),
            "chosen_audio": chosenAudio ?? "",
            "chosen_subtitle": chosenSubtitle ?? "",
            "why": why?.word ?? "",
        ]
    }

    /// One track as the bridge carries it.
    private static func track(_ track: OfferedTrack) -> [String: Any] {
        ["id": track.id, "language": track.language, "label": track.label]
    }
}
