import UIKit

/// This app's own page in the phone's settings, on iOS.
///
/// Namespace: `Lemonfiber.Settings.*`
///
/// One request: leave the app for its settings page, where a permission it was
/// refused is granted again. The answer says whether the page was asked for, so
/// a screen whose button did nothing can say so rather than look broken.
///
/// Opening a URL is a main-thread operation and the bridge call may arrive on
/// another, so this hops and waits, the way `HandoverFunctions` presents.
enum SettingsFunctions {
    private static let tag = "Lemonfiber"

    /// Ask for this app's settings page, and say whether it could be asked for.
    private static func openTheAppsPage() -> Bool {
        var asked = false
        let waiting = DispatchSemaphore(value: 0)

        DispatchQueue.main.async {
            defer { waiting.signal() }

            guard let page = URL(string: UIApplication.openSettingsURLString),
                UIApplication.shared.canOpenURL(page)
            else {
                return
            }

            UIApplication.shared.open(page)
            asked = true
        }

        waiting.wait()

        return asked
    }

    class Open: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let said = SettingsRule(pageWasAskedFor: openTheAppsPage()).said

            NSLog("%@ settings: %@", tag, said.word)

            return Envelope.of(said.word).asAnswer()
        }
    }
}
