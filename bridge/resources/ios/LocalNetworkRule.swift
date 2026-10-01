/// Whether the platform refuses this app the local network on the way to one address.
///
/// Two answers and no third. Only the refusal is asked for: the platform
/// reports why a path cannot be taken, and one reason of them is this app not
/// being allowed onto the local network.
///
/// Deliberately mirrors `LocalNetworkRule.kt` line for line.
public enum WhetherTheLocalNetworkIsOpen: String, Sendable {
    /// Nothing about this app is refused on the way. The app reports what it meets.
    case open = "open"

    /// The platform refuses this app the local network. The remedy is a switch in Settings.
    case forbidden = "forbidden"

    /// What this answer is called on the wire.
    public var word: String { rawValue }
}

/// What the platform's account of one path means.
///
/// A refused permission and a switched-off machine both arrive as silence at
/// the socket. The platform knows which it was, and says so in why the path
/// cannot be taken; this reads that one reason and nothing else.
///
/// No Apple framework in sight, for `LinkRule`'s reason.
///
/// Deliberately mirrors `LocalNetworkRule.kt` line for line.
public struct LocalNetworkRule: Equatable, Sendable {
    /// Whether the platform said anything about the path before the question was given up on.
    public let pathWasReadable: Bool

    /// Whether the reason the path cannot be taken is that this app may not use the local network.
    public let platformDeniedIt: Bool

    /// Built from what the platform said, or from it having said nothing.
    public init(pathWasReadable: Bool, platformDeniedIt: Bool) {
        self.pathWasReadable = pathWasReadable
        self.platformDeniedIt = platformDeniedIt
    }

    /// What to tell the app, read in this order.
    ///
    /// A platform that said nothing answers **open**, for `LinkRule`'s reason:
    /// the app then reports the stack as not answering, which is what it always
    /// did, rather than sending somebody to a switch that may not exist.
    public var said: WhetherTheLocalNetworkIsOpen {
        if !pathWasReadable {
            return .open
        }

        return platformDeniedIt ? .forbidden : .open
    }

    /// A platform that let the path be taken.
    public static let permitted = LocalNetworkRule(pathWasReadable: true, platformDeniedIt: false)

    /// A platform that said nothing, which answers open.
    public static let unasked = LocalNetworkRule(pathWasReadable: false, platformDeniedIt: false)
}
