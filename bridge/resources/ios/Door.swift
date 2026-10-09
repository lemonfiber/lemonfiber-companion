import Foundation

/// The household's front door, as the origin every request the player makes must stay at.
///
/// The core states where a title streams from, and that location names the
/// door: its scheme, its host and its port. The player asks nothing of any
/// other origin. A redirect elsewhere, a playlist naming a segment on another
/// machine, a subtitle on a public server: each is refused rather than
/// followed, because a request off the door is a request the pin does not
/// cover and the core did not state.
///
/// **Only `https`, and never with a name or password in the address.** A door
/// reached in the clear has nothing to pin, and credentials written into an
/// address end up in logs and caches; the grant travels in a header instead.
///
/// Deliberately mirrors `Door.kt` line for line.
public struct Door: Equatable, Sendable {
    /// The one scheme a door is reached over.
    public static let scheme = "https"

    /// The port `https` means where an address names none.
    private static let defaultPort = 443

    /// The door's host, in lower case.
    public let host: String

    /// The door's port, with the default written out.
    public let port: Int

    private init(host: String, port: Int) {
        self.host = host
        self.port = port
    }

    /// The door a location the core stated names, or nil where it names none.
    ///
    /// - Parameter location: where the core said a title streams from.
    /// - Returns: the door, or nil.
    public static func of(_ location: String) -> Door? {
        parsed(location)?.door
    }

    /// Whether an address is at this door.
    ///
    /// - Parameter address: an absolute address.
    /// - Returns: whether its scheme, host and port are the door's.
    public func holds(_ address: String) -> Bool {
        admitted(address) != nil
    }

    /// An address at this door, parsed as the platform fetches it, or nil where it is not at the door.
    ///
    /// What is fetched is the very address that was checked: the caller hands
    /// the platform this, never the text it came from parsed a second time.
    ///
    /// - Parameter address: an absolute address.
    /// - Returns: the address, or nil.
    public func admitted(_ address: String) -> URL? {
        guard let parsed = Door.parsed(address), parsed.door == self else {
            return nil
        }

        return parsed.url
    }

    /// An address read twice, by this rule and by the platform, and kept only where the two agree.
    ///
    /// Two readers of one address are two chances to disagree about where it
    /// goes, and a disagreement is how a check passes on one host while the
    /// fetch goes to another. So the authority is read here by a grammar with
    /// no corners — `https://`, a host of plain letters, digits, hyphens and
    /// dots, and an optional port written as a plain number — and the
    /// platform's reading must name the same host and port with no name or
    /// password. Everything the grammar has no place for is refused rather
    /// than interpreted: a name or password before the host, a percent escape
    /// or a letter outside ASCII in it, a trailing dot, a bare IPv6 address, a
    /// backslash, a space or a control character anywhere.
    private static func parsed(_ address: String) -> (door: Door, url: URL)? {
        guard let written = authority(of: address), let url = URL(string: address), agrees(url, with: written)
        else {
            return nil
        }

        return (Door(host: written.host, port: written.port ?? defaultPort), url)
    }

    /// Whether the platform's reading names the host and port the grammar read, and nothing else.
    ///
    /// Any name or password before the host gives the platform a user, even an
    /// empty one, so asking for none asks for both.
    static func agrees(_ url: URL, with written: (host: String, port: Int?)) -> Bool {
        url.scheme?.lowercased() == scheme && url.user == nil && url.host?.lowercased() == written.host
            && url.port == written.port
    }

    /// The host and port an address names, by the grammar above, or nil where it falls outside it.
    static func authority(of address: String) -> (host: String, port: Int?)? {
        guard address.unicodeScalars.allSatisfy({ (0x21...0x7E).contains($0.value) && $0 != "\\" }),
            address.lowercased().hasPrefix(scheme + "://")
        else {
            return nil
        }

        let rest = address.dropFirst(scheme.count + 3)
        let named = rest.prefix { !"/?#".contains($0) }.lowercased()
        let parts = named.split(separator: ":", omittingEmptySubsequences: false).map(String.init)

        guard parts.count <= 2, isAHost(parts[0]) else {
            return nil
        }

        guard parts.count == 2 else {
            return (parts[0], nil)
        }

        return port(parts[1]).map { (parts[0], $0) }
    }

    /// Whether a name is dot-separated labels of letters, digits and inner hyphens.
    private static func isAHost(_ name: String) -> Bool {
        let labels = name.split(separator: ".", omittingEmptySubsequences: false)

        return labels.allSatisfy { label in
            !label.isEmpty && label.first != "-" && label.last != "-"
                && label.allSatisfy { ("a"..."z").contains($0) || ("0"..."9").contains($0) || $0 == "-" }
        }
    }

    /// A port written as a plain number a connection can use, or nil.
    private static func port(_ written: String) -> Int? {
        guard written.first != "0", written.allSatisfy({ ("0"..."9").contains($0) }),
            let number = Int(written), (1...65_535).contains(number)
        else {
            return nil
        }

        return number
    }

    /// An address a document at the door names, made absolute, or nil where it is not at the door.
    ///
    /// - Parameters:
    ///   - reference: the address as the document wrote it, relative or absolute.
    ///   - base: the address of the document that named it.
    /// - Returns: the absolute address, or nil.
    public func resolve(_ reference: String, against base: String) -> String? {
        guard let from = URL(string: base),
            let resolved = URL(string: reference, relativeTo: from)?.absoluteString,
            holds(resolved)
        else {
            return nil
        }

        return resolved
    }
}
