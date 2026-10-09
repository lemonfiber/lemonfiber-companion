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
        guard let parts = URLComponents(string: location),
            parts.scheme?.lowercased() == scheme,
            parts.user == nil,
            parts.password == nil,
            let host = parts.host, !host.isEmpty
        else {
            return nil
        }

        return Door(host: host.lowercased(), port: parts.port ?? defaultPort)
    }

    /// Whether an address is at this door.
    ///
    /// - Parameter address: an absolute address.
    /// - Returns: whether its scheme, host and port are the door's.
    public func holds(_ address: String) -> Bool {
        Door.of(address) == self
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
