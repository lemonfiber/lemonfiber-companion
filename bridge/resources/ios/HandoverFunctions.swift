import Foundation
import UIKit

/// Handing something over, on iOS.
///
/// Namespace: `Lemonfiber.Handover.*`
///
/// The platform's own share sheet, given a report to put in front of somebody
/// who can help. Where it goes is a choice a person makes in an app this one
/// does not know about, which is the whole difference between this and the
/// crash reporter this application may not have.
///
/// The decision worth testing is not here. `HandoverRule` holds it — the two
/// refusals and the order they are read in — and it runs on a laptop with no
/// device in sight.
///
/// **It hands over text and writes no file.** `UIActivityViewController` will
/// take a file URL, and taking one would mean writing a diagnostic report into
/// a cache directory and leaving it there, because nothing here ever learns
/// when the chosen app is done with it. A `String` activity item needs none of
/// that. Nothing in this bridge writes to a cache, and this is the one function
/// that would have.
///
/// **What the operator chose is never asked for.** `completionWithItemsHandler`
/// reports the activity type that was picked, and it is deliberately not set:
/// reporting which app received a diagnostic report would be this application
/// learning something about the operator it has no reason to know.
///
/// **Nothing of the report reaches a log line.** Not the title, not a byte of
/// the text. The one line here carries the outcome word.
enum HandoverFunctions {
    /// What this plugin's log lines are tagged with.
    private static let tag = "Lemonfiber"

    /// The word the sheet having been presented is called.
    private static let offered = "offered"

    /// The word every refusal this capability makes is called.
    private static let refused = "refused"

    /// Ask for the sheet on the main queue, and say whether it was presented.
    ///
    /// Presenting is a main-thread operation and the bridge call may arrive on
    /// another, so this hops and waits. A sheet that cannot find a view
    /// controller to present from is a refusal rather than a crash: what the
    /// operator needs is a sentence saying it did not open.
    private static func present(title: String, text: String) -> Bool {
        var presented = false
        let waiting = DispatchSemaphore(value: 0)

        DispatchQueue.main.async {
            defer { waiting.signal() }

            guard
                let root = UIApplication.shared.connectedScenes
                    .compactMap({ $0 as? UIWindowScene })
                    .flatMap({ $0.windows })
                    .first(where: { $0.isKeyWindow })?
                    .rootViewController
            else {
                return
            }

            let sheet = UIActivityViewController(activityItems: [text], applicationActivities: nil)
            sheet.setValue(title, forKey: "subject")

            // Where the sheet has to be anchored, which an iPad requires and a
            // phone ignores. Without it this raises on an iPad rather than
            // presenting, which would be a crash on the one screen an operator
            // reached by asking for help.
            sheet.popoverPresentationController?.sourceView = root.view
            sheet.popoverPresentationController?.sourceRect = CGRect(
                x: root.view.bounds.midX, y: root.view.bounds.midY, width: 0, height: 0)

            root.present(sheet, animated: true)
            presented = true
        }

        waiting.wait()

        return presented
    }

    /// Put a report in front of whoever the operator picks.
    class Offer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let title = parameters["title"] as? String ?? ""
            let text = parameters["text"] as? String ?? ""

            let rule = HandoverRule(
                thereIsSomethingToHandOver: !text.isEmpty,
                theSheetWasPresented: !text.isEmpty && present(title: title, text: text)
            )

            guard let why = rule.whyNot else {
                NSLog("%@ handover: %@", tag, offered)

                return Envelope.of(offered).asAnswer()
            }

            NSLog("%@ handover: refused, %@", tag, why.word)

            return Envelope.refusing(refused, why.word).asAnswer()
        }
    }
}
