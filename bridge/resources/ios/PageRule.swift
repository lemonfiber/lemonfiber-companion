/// Whether a page outside the app was opened.
///
/// Deliberately mirrors `PageRule.kt` line for line.
public enum WhetherThePageOpened: String, Sendable {
    /// The page was handed to the phone's browser.
    case opened = "opened"

    /// The address was not an https page, or nothing would open it.
    case refused = "refused"

    /// What this answer is called on the wire.
    public var word: String { rawValue }
}

/// Which pages the app opens, and what asking for one came to.
///
/// Only an https address naming somewhere is opened: nothing else the app is
/// handed leaves it for the browser. No Apple framework in sight, for
/// `LinkRule`'s reason.
///
/// Deliberately mirrors `PageRule.kt` line for line.
public struct PageRule: Equatable, Sendable {
    /// The one scheme a page is opened under.
    private static let https = "https://"

    /// The address the app asked to open.
    public let address: String

    /// Built from the address the app asked to open.
    public init(address: String) {
        self.address = address
    }

    /// Whether this address is a page the app opens.
    public var mayOpen: Bool {
        address.lowercased().hasPrefix(Self.https)
            && address.count > Self.https.count
            && !address.unicodeScalars.contains { scalar in
                scalar.properties.isWhitespace || scalar.properties.generalCategory == .control
            }
    }

    /// What to tell the app, given whether the platform took the request.
    public func said(pageWasAskedFor: Bool) -> WhetherThePageOpened {
        mayOpen && pageWasAskedFor ? .opened : .refused
    }
}
