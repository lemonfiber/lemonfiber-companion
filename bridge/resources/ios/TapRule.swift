import Foundation

/// Where a tap on a notification may lead: back into this app, and nowhere else.
///
/// The link a notification is handed is opened by the platform, so a link
/// under any other scheme would be a notification that opens somebody else's
/// app or a web page. Only a link under the scheme this app is opened at, naming
/// a screen, is kept; anything else leaves the notification leading nowhere.
///
/// No Apple framework beyond Foundation, so it runs in SwiftPM on a laptop.
///
/// Deliberately mirrors `TapRule.kt` line for line.
public enum TapRule {
    /// The scheme this app is opened at, which every join link is written under too.
    public static let scheme = "lemonfiber"

    /// The link, where it leads into this app; nil where it leads anywhere else or is no link at all.
    public static func opens(_ said: String?) -> String? {
        guard
            let said,
            let link = URLComponents(string: said),
            link.scheme == scheme,
            let screen = link.host, !screen.isEmpty,
            link.user == nil, link.password == nil, link.port == nil
        else {
            return nil
        }

        return said
    }
}
