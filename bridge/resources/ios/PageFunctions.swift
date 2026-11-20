import UIKit

/// A page outside the app, opened in the phone's browser, on iOS.
///
/// Namespace: `Lemonfiber.Page.*`
///
/// One request: hand an https page to the phone's browser. Any other address is
/// refused before anything is asked of the platform.
///
/// Opening a URL is a main-thread operation and the bridge call may arrive on
/// another, so this hops and waits, the way `SettingsFunctions` does.
enum PageFunctions {
    private static let tag = "Lemonfiber"

    /// The parameter carrying the address.
    private static let address = "address"

    /// Hand the page to the browser, and say whether it could be asked for.
    private static func open(_ page: String) -> Bool {
        var asked = false
        let waiting = DispatchSemaphore(value: 0)

        DispatchQueue.main.async {
            defer { waiting.signal() }

            guard let url = URL(string: page) else {
                return
            }

            UIApplication.shared.open(url)
            asked = true
        }

        waiting.wait()

        return asked
    }

    class Open: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let rule = PageRule(address: parameters[PageFunctions.address] as? String ?? "")
            let said = rule.said(pageWasAskedFor: rule.mayOpen && open(rule.address))

            NSLog("%@ page: %@", tag, said.word)

            return Envelope.of(said.word).asAnswer()
        }
    }
}
