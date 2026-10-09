/// An HLS playlist, read so that every address in it stays at the door.
///
/// A playlist is a list of addresses: the renditions a master playlist offers,
/// the segments a media playlist plays, the key, the initialisation section and
/// the subtitles its tags name. The player follows every one of them, so every
/// one of them is read here first. Each is made absolute against the playlist's
/// own address and kept only where it is at the door; **one address off the
/// door refuses the whole playlist**, because a playlist that sends the player
/// anywhere else is not one the core stated, whatever the rest of it says.
///
/// Where the platform's player has to be handed addresses under a private
/// scheme so that every fetch comes back through the pinned connection, the
/// addresses are written under that scheme. Where it reads them as they are,
/// the scheme stays `https` and the rule still refuses what is off the door.
///
/// Lines are read whether they end in a line feed or a carriage return and a
/// line feed, and written with line feeds.
///
/// Deliberately mirrors `PlaylistRule.kt` line for line.
public struct PlaylistRule: Sendable {
    /// What opens an address inside a tag.
    private static let attributeOpens = "URI=\""

    /// What closes it.
    private static let attributeCloses: Character = "\""

    /// The door every address must be at.
    public let door: Door

    /// The scheme addresses are handed to the player under.
    public let scheme: String

    /// A rule for one door, writing addresses under one scheme.
    public init(door: Door, scheme: String) {
        self.door = door
        self.scheme = scheme
    }

    /// The playlist with every address made absolute and at the door, or nil where one is not.
    ///
    /// - Parameters:
    ///   - playlist: the playlist as the door served it.
    ///   - address: where it was served from.
    /// - Returns: the playlist as the player is to read it, or nil.
    public func rewrite(_ playlist: String, at address: String) -> String? {
        var written: [String] = []

        // A carriage return and a line feed are one character to Swift, so
        // both endings are named rather than the line feed alone.
        for line in playlist.split(
            omittingEmptySubsequences: false, whereSeparator: { $0 == "\n" || $0 == "\r\n" })
        {
            guard let kept = rewriteLine(String(line), at: address) else {
                return nil
            }

            written.append(kept)
        }

        return written.joined(separator: "\n")
    }

    /// One line, with its addresses rewritten, or nil where one is off the door.
    private func rewriteLine(_ line: String, at address: String) -> String? {
        if line.isEmpty {
            return line
        }

        if line.hasPrefix("#") {
            return rewriteAttributes(line, at: address)
        }

        return handed(line, at: address)
    }

    /// A tag, with every `URI` attribute in it rewritten.
    private func rewriteAttributes(_ tag: String, at address: String) -> String? {
        var rest = Substring(tag)
        var written = ""

        while let opens = rest.range(of: Self.attributeOpens) {
            written += rest[..<opens.upperBound]
            rest = rest[opens.upperBound...]

            guard let closes = rest.firstIndex(of: Self.attributeCloses),
                let kept = handed(String(rest[..<closes]), at: address)
            else {
                return nil
            }

            written += kept
            rest = rest[closes...]
        }

        return written + rest
    }

    /// One address, absolute and under the player's scheme, or nil where it is off the door.
    private func handed(_ reference: String, at address: String) -> String? {
        guard let absolute = door.resolve(reference, against: address) else {
            return nil
        }

        return scheme + absolute.dropFirst(Door.scheme.count)
    }
}
