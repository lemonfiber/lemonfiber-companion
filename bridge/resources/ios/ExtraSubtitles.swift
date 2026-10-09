/// Subtitles an extension offers beside a stream, joined into the stream's own master playlist.
///
/// A subtitle file at the door that the stream does not list — one an
/// extension found, or one a member added — has no place in an HLS stream
/// until a playlist names it. So each is joined to the stream's master
/// playlist as a subtitle rendition, under a private scheme whose playlists
/// are written here rather than fetched: one segment, the file itself, said to
/// last as long as anything plays. The file is fetched like every other byte,
/// through the door and over the pin.
///
/// Only a master playlist is joined; a media playlist or a file played directly
/// is handed back as it was. Each rendition joins the subtitle group the
/// stream's variants already name, or a group of its own that every variant is
/// given. A label is stripped of what a quoted value cannot hold, and a
/// language is written only where it is a plain tag.
///
/// On Android the platform's player takes a subtitle file beside a stream as
/// it is, so nothing there asks this; it is mirrored so both halves state the
/// same rule.
///
/// Deliberately mirrors `ExtraSubtitles.kt` line for line.
public struct ExtraSubtitles: Sendable {
    /// The scheme a joined rendition's playlist is handed to the player under.
    public static let scheme = "lfextra"

    /// The subtitle group joined renditions form where the stream names none.
    static let group = "lf-extra"

    /// How long a joined subtitle file is said to last, in seconds: longer than anything plays.
    static let lasting = 86_400

    /// What opens the address of a joined rendition's playlist.
    private static let opens = scheme + "://track/"

    /// The subtitle tracks to join, in the order they were offered.
    public let tracks: [ExtraTrack]

    /// The subtitles among some offered tracks.
    public init(_ offered: [ExtraTrack]) {
        tracks = offered.filter { $0.kind == .subtitle }
    }

    /// The address the player is handed for one joined rendition's playlist.
    public static func address(of index: Int) -> String {
        opens + String(index)
    }

    /// The track a handed address names, or nil where it names none.
    public func track(at address: String) -> ExtraTrack? {
        let written = address.dropFirst(Self.opens.count)

        guard address.hasPrefix(Self.opens), !written.isEmpty,
            written.allSatisfy({ ("0"..."9").contains($0) }),
            let index = Int(written), tracks.indices.contains(index)
        else {
            return nil
        }

        return tracks[index]
    }

    /// The playlist with every track joined as a subtitle rendition, where it is a master playlist.
    ///
    /// - Parameter playlist: a playlist already read through `PlaylistRule`.
    /// - Returns: the playlist with the renditions joined, or as it was.
    public func join(_ playlist: String) -> String {
        let lines = playlist.split(separator: "\n", omittingEmptySubsequences: false).map(String.init)

        guard !tracks.isEmpty, lines.first == "#EXTM3U", lines.contains(where: Self.isAVariant) else {
            return playlist
        }

        let group = lines.lazy.compactMap(Self.subtitleGroup(of:)).first ?? Self.group
        let renditions = tracks.indices.map { rendition($0, in: group) }
        let variants = lines.dropFirst().map { line in
            Self.isAVariant(line) && Self.subtitleGroup(of: line) == nil
                ? line + ",SUBTITLES=\"" + group + "\"" : line
        }

        return ([lines[0]] + renditions + variants).joined(separator: "\n")
    }

    /// The media playlist that plays one subtitle file for as long as anything plays.
    ///
    /// - Parameter address: the file, as the player is handed it.
    /// - Returns: the playlist.
    public static func playlist(playing address: String) -> String {
        [
            "#EXTM3U",
            "#EXT-X-VERSION:3",
            "#EXT-X-TARGETDURATION:\(lasting)",
            "#EXT-X-MEDIA-SEQUENCE:0",
            "#EXT-X-PLAYLIST-TYPE:VOD",
            "#EXTINF:\(lasting),",
            address,
            "#EXT-X-ENDLIST",
            "",
        ].joined(separator: "\n")
    }

    /// One track as a subtitle rendition in a group.
    private func rendition(_ index: Int, in group: String) -> String {
        let track = tracks[index]
        let label = String(track.label.filter(Self.isQuotable))
        let name = label.isEmpty ? "Subtitles \(index + 1)" : label
        let language =
            !track.language.isEmpty && track.language.allSatisfy(Self.isATagCharacter)
            ? ",LANGUAGE=\"" + track.language + "\"" : ""

        return "#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID=\"" + group + "\",NAME=\"" + name + "\"" + language
            + ",AUTOSELECT=NO,DEFAULT=NO,URI=\"" + Self.address(of: index) + "\""
    }

    /// Whether a quoted value can hold a character: not a quote, and not a control like a line break.
    private static func isQuotable(_ character: Character) -> Bool {
        character != "\"" && (character.asciiValue ?? 0x20) >= 0x20
    }

    /// Whether a character belongs in a plain language tag.
    private static func isATagCharacter(_ character: Character) -> Bool {
        character.isASCII && (character.isLetter || character.isNumber || character == "-")
    }

    /// Whether a line names a variant of the stream.
    private static func isAVariant(_ line: String) -> Bool {
        line.uppercased().hasPrefix("#EXT-X-STREAM-INF:")
    }

    /// The subtitle group a variant names, or nil.
    private static func subtitleGroup(of line: String) -> String? {
        guard isAVariant(line), let colon = line.firstIndex(of: ":"),
            let attributes = PlaylistRule.split(line[line.index(after: colon)...])
        else {
            return nil
        }

        return attributes.lazy.compactMap { attribute -> String? in
            let parts = attribute.split(separator: "=", maxSplits: 1).map(String.init)

            guard parts.count == 2, parts[0].uppercased() == "SUBTITLES", parts[1].count >= 2,
                parts[1].hasPrefix("\""),
                parts[1].hasSuffix("\"")
            else {
                return nil
            }

            return String(parts[1].dropFirst().dropLast())
        }.first
    }
}
