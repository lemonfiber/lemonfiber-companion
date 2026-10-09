import Foundation

/// The addresses of one playback's relay: the door's, handed to the player at loopback under that playback's token.
public struct RelayRule: Sendable {
    /// The only address the relay listens on.
    public static let host = "127.0.0.1"

    /// Where an extra subtitle's playlist is asked for, beneath the token.
    static let extras = "/~extra/"

    private static let methods: Set<String> = ["GET", "HEAD"]

    private static let reasons = [
        200: "OK", 206: "Partial Content", 400: "Bad Request", 404: "Not Found",
        405: "Method Not Allowed", 502: "Bad Gateway",
    ]

    private let door: Door

    private let token: String

    private let port: UInt16

    /// The relay for one playback at `door`, asked for under `token` at loopback `port`.
    public init(door: Door, token: String, port: UInt16) {
        self.door = door
        self.token = token
        self.port = port
    }

    private var origin: String {
        "http://\(Self.host):\(port)"
    }

    /// The loopback address a door address is handed to the player at, or nil where the door does not hold it.
    public func handed(_ address: String) -> String? {
        target(of: address).map { origin + $0 }
    }

    /// The request target the player asks for a door address by, or nil where the door does not hold it.
    public func target(of address: String) -> String? {
        guard let url = door.admitted(address),
            let parts = URLComponents(url: url, resolvingAgainstBaseURL: false)
        else {
            return nil
        }

        let path = parts.percentEncodedPath.isEmpty ? "/" : parts.percentEncodedPath

        return "/" + token + path + (parts.percentEncodedQuery.map { "?" + $0 } ?? "")
    }

    /// Where an extra subtitle's playlist is handed to the player.
    public func handedExtra(_ index: Int) -> String {
        origin + "/" + token + Self.extras + String(index)
    }

    /// A joined master playlist with each extra subtitle's address handed at the relay.
    public func handingExtras(in joined: String, count: Int) -> String {
        (0..<count).reduce(joined) { written, index in
            written.replacingOccurrences(
                of: "\"" + ExtraSubtitles.address(of: index) + "\"", with: "\"" + handedExtra(index) + "\"")
        }
    }

    /// The door address a request to the relay asks for, or nil where it is not this playback's.
    public func asked(_ target: String) -> URL? {
        guard let rest = beneath(target), !rest.hasPrefix(Self.extras) else {
            return nil
        }

        return door.admitted("\(Door.scheme)://\(door.host):\(door.port)\(rest)")
    }

    /// Which extra subtitle a request to the relay asks for, or nil where it asks for none.
    public func extra(_ target: String) -> Int? {
        guard let rest = beneath(target), rest.hasPrefix(Self.extras) else {
            return nil
        }

        let written = rest.dropFirst(Self.extras.count)

        guard !written.isEmpty, written.count <= 4, written.allSatisfy({ ("0"..."9").contains($0) }) else {
            return nil
        }

        return Int(written)
    }

    /// What follows this playback's token in a request target, or nil where the token is not this playback's.
    private func beneath(_ target: String) -> String? {
        let prefix = "/" + token

        guard target.hasPrefix(prefix + "/") else {
            return nil
        }

        return String(target.dropFirst(prefix.count))
    }

    /// One request the relay was sent, read from its head.
    public struct Request: Equatable, Sendable {
        /// GET or HEAD.
        public let method: String
        /// The path and query asked for.
        public let target: String
        /// The byte range asked for, as written, or nil where the whole answer is.
        public let range: String?
    }

    /// The request a head says, or nil where it is not one the relay answers.
    public static func read(_ head: String) -> Request? {
        let lines = head.components(separatedBy: "\r\n")
        let first = lines[0].split(separator: " ", omittingEmptySubsequences: false).map(String.init)

        guard first.count == 3, methods.contains(first[0]), first[1].hasPrefix("/"),
            first[2].hasPrefix("HTTP/1.")
        else {
            return nil
        }

        let range = lines.dropFirst().lazy.compactMap { line -> String? in
            guard let colon = line.firstIndex(of: ":"), line[..<colon].lowercased() == "range" else {
                return nil
            }

            return line[line.index(after: colon)...].trimmingCharacters(in: .whitespaces)
        }.first

        return Request(method: first[0], target: first[1], range: range)
    }

    /// The head of an answer, closing the connection after it.
    public static func head(status: Int, headers: [(String, String)]) -> String {
        let said = "HTTP/1.1 \(status) \(reasons[status] ?? "Status")\r\n"
        let named = headers.map { "\($0.0): \($0.1)\r\n" }.joined()

        return said + named + "Connection: close\r\n\r\n"
    }
}
