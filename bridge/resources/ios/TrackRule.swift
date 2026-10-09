/// A sound or subtitle track the stream offers.
///
/// Deliberately mirrors `TrackRule.kt` line for line.
public struct OfferedTrack: Equatable, Sendable {
    /// What the player calls it.
    public let id: String

    /// Its language, as a tag such as `en` or `nl-BE`, or empty where the stream does not say.
    public let language: String

    /// What the stream labels it.
    public let label: String

    /// Whether the stream marks it as the one to play unless asked otherwise.
    public let isDefault: Bool

    /// A track as the stream offered it.
    public init(id: String, language: String, label: String, isDefault: Bool) {
        self.id = id
        self.language = language
        self.label = label
        self.isDefault = isDefault
    }
}

/// Which sound and which subtitles to start with.
///
/// The member's preference first, matched on the language and not on the
/// region, so a preference for English plays the British track where that is
/// the only English one. **Sound always plays**: without a match it is the
/// stream's own default, and without one of those the first. **Subtitles are
/// never forced on**: without a match there are none, because subtitles in a
/// language the member did not ask for are a screen covered in words they
/// cannot read.
///
/// Deliberately mirrors `TrackRule.kt` line for line.
public enum TrackRule {
    /// The member's word for no subtitles.
    public static let off = "off"

    /// The sound to start with, or nil where the stream offers none.
    ///
    /// - Parameters:
    ///   - offered: every sound track the stream offers.
    ///   - preferred: the language the member prefers, or empty.
    /// - Returns: the track's id, or nil.
    public static func audio(offered: [OfferedTrack], preferred: String) -> String? {
        let chosen =
            matching(offered, preferred) ?? offered.first(where: \.isDefault) ?? offered.first

        return chosen?.id
    }

    /// The subtitles to start with, or nil for none.
    ///
    /// - Parameters:
    ///   - offered: every subtitle track the stream offers.
    ///   - preferred: the language the member prefers, empty or `off` for none.
    /// - Returns: the track's id, or nil.
    public static func subtitle(offered: [OfferedTrack], preferred: String) -> String? {
        preferred == off ? nil : matching(offered, preferred)?.id
    }

    /// The first track in the preferred language, or nil.
    private static func matching(_ offered: [OfferedTrack], _ preferred: String) -> OfferedTrack? {
        let wanted = primary(preferred)

        guard !wanted.isEmpty else {
            return nil
        }

        return offered.first { primary($0.language) == wanted }
    }

    /// A language tag's language, without its region or script, in lower case.
    private static func primary(_ tag: String) -> String {
        String(tag.prefix { $0 != "-" && $0 != "_" }).lowercased()
    }
}
