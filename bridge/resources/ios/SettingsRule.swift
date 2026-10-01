/// Whether this app's settings page was asked for.
///
/// Two answers and no third.
///
/// Deliberately mirrors `SettingsRule.kt` line for line.
public enum WhetherTheSettingsOpened: String, Sendable {
    /// The page was asked for, and the platform has it in front of the operator.
    case opened = "opened"

    /// The platform would not open it. The screen says so.
    case refused = "refused"

    /// What this answer is called on the wire.
    public var word: String { rawValue }
}

/// What asking for the settings page came to.
///
/// No Apple framework in sight, for `LinkRule`'s reason.
///
/// Deliberately mirrors `SettingsRule.kt` line for line.
public struct SettingsRule: Equatable, Sendable {
    /// Whether the platform took the request for the page.
    public let pageWasAskedFor: Bool

    /// Built from whether the platform took the request.
    public init(pageWasAskedFor: Bool) {
        self.pageWasAskedFor = pageWasAskedFor
    }

    /// What to tell the app.
    public var said: WhetherTheSettingsOpened {
        pageWasAskedFor ? .opened : .refused
    }
}
