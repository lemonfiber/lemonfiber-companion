import Foundation

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
/// **Read the way the player reads it, and closed where that is not certain.**
/// A tag's attributes are split at the commas outside quoted values, so a
/// quoted value holding a comma, or holding the letters `URI="`, is one value
/// of one attribute. Every attribute whose name ends in `URI`, and the
/// interstitials' `X-ASSET-LIST`, is an address and must be a quoted string.
/// Any other value, and any other part of a tag, that reads as an absolute
/// address — a scheme, `://` anywhere, or `//` — refuses the playlist, because the player
/// fetches an absolute address by itself. A relative one in a value this rule
/// does not know is harmless: the player resolves it against the playlist's
/// own address, which is under the private scheme. A playlist that defines
/// variables is refused, because the player substitutes them into addresses
/// this rule never sees whole. Comments, which the player ignores, are dropped,
/// and tags are recognised whatever case they are written in.
///
/// Lines are read whether they end in a line feed or a carriage return and a
/// line feed, and written with line feeds.
///
/// Deliberately mirrors `PlaylistRule.kt` line for line.
public struct PlaylistRule: Sendable {
    /// What marks a variable in use, in a line or a value.
    private static let variable = "{$"

    /// The tags a playlist is refused for: they define variables.
    private static let refusedTags: Set<String> = ["EXT-X-DEFINE"]

    /// The attributes that name an address without ending in `URI`.
    private static let addressAttributes: Set<String> = ["X-ASSET-LIST"]

    /// What every tag begins with, in any case.
    private static let tagOpens = "#EXT"

    /// What is trimmed from around an address line: spaces and tabs.
    private static let blank = CharacterSet(charactersIn: " \t")

    /// The characters a scheme is written in after its first letter.
    private static let schemeMarks = "+.-"

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
        guard !playlist.contains(Self.variable) else {
            return nil
        }

        var written: [String] = []

        // A carriage return and a line feed are one character to Swift, so
        // both endings are named rather than the line feed alone.
        for line in playlist.split(
            omittingEmptySubsequences: false, whereSeparator: { $0 == "\n" || $0 == "\r\n" })
        {
            guard let kept = rewriteLine(String(line), at: address) else {
                return nil
            }

            written += kept
        }

        return written.joined(separator: "\n")
    }

    /// One line as the player is to read it: itself rewritten, nothing for a comment, or nil where it is refused.
    private func rewriteLine(_ line: String, at address: String) -> [String]? {
        if line.isEmpty {
            return [line]
        }

        if line.hasPrefix("#") {
            return line.uppercased().hasPrefix(Self.tagOpens)
                ? rewriteTag(line, at: address).map { [$0] } : []
        }

        return handed(line.trimmingCharacters(in: Self.blank), at: address).map { [$0] }
    }

    /// A tag, with every address among its attributes rewritten, or nil where one cannot be.
    private func rewriteTag(_ tag: String, at address: String) -> String? {
        guard let colon = tag.firstIndex(of: ":") else {
            return tag
        }

        let name = tag[tag.index(after: tag.startIndex)..<colon]

        guard !Self.refusedTags.contains(name.uppercased()),
            let attributes = Self.split(tag[tag.index(after: colon)...])
        else {
            return nil
        }

        var written: [String] = []

        for attribute in attributes {
            guard let kept = rewriteAttribute(attribute, at: address) else {
                return nil
            }

            written.append(kept)
        }

        return String(tag[...colon]) + written.joined(separator: ",")
    }

    /// One attribute, its address rewritten where it names one, or nil where it is refused.
    private func rewriteAttribute(_ attribute: String, at address: String) -> String? {
        guard let equals = attribute.firstIndex(of: "="), !attribute[..<equals].contains("\"") else {
            return Self.readsAsAnAddress(attribute) ? nil : attribute
        }

        let name = attribute[..<equals]
        let value = attribute[attribute.index(after: equals)...]
        let upper = name.uppercased()

        guard upper.hasSuffix("URI") || Self.addressAttributes.contains(upper) else {
            return Self.readsAsAnAddress(String(value)) ? nil : attribute
        }

        guard value.count >= 2, value.first == "\"", value.last == "\"",
            let kept = handed(String(value.dropFirst().dropLast()), at: address)
        else {
            return nil
        }

        return String(name) + "=\"" + kept + "\""
    }

    /// One address, absolute and under the player's scheme, or nil where it is off the door.
    ///
    /// An empty address resolves to nothing here, so it is refused as `PlaylistRule.kt` refuses it.
    private func handed(_ reference: String, at address: String) -> String? {
        guard let absolute = door.resolve(reference, against: address) else {
            return nil
        }

        return scheme + absolute.dropFirst(Door.scheme.count)
    }

    /// A tag's value split at the commas outside quoted strings, or nil where a quote is left open.
    static func split(_ value: Substring) -> [String]? {
        var attributes: [String] = []
        var current = ""
        var quoted = false

        for character in value {
            if character == "," && !quoted {
                attributes.append(current)
                current = ""
            } else {
                quoted = character == "\"" ? !quoted : quoted
                current.append(character)
            }
        }

        return quoted ? nil : attributes + [current]
    }

    /// Whether a value the player does not take as an address could still be fetched as one:
    /// it names a scheme, holds `://` anywhere, or begins with `//`.
    private static func readsAsAnAddress(_ value: String) -> Bool {
        let bare = value.trimmingCharacters(in: Self.blank.union(CharacterSet(charactersIn: "\"")))
        let scheme = bare.prefix { $0.isASCII && ($0.isLetter || $0.isNumber || schemeMarks.contains($0)) }

        return bare.contains("://") || bare.hasPrefix("//") || bare.hasPrefix("\\\\")
            || (bare.first?.isLetter == true && bare.dropFirst(scheme.count).hasPrefix(":"))
    }
}
