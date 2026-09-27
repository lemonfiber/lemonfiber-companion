import Foundation
import UIKit

/// Handing something over, on iOS.
///
/// Namespace: `Lemonfiber.Handover.*`
///
/// The platform's own share sheet, given text or a file to put in front of
/// somebody who can help. Where it goes is a choice a person makes in an app
/// this one does not know about, which is the whole difference between this and
/// the crash reporter this application may not have.
///
/// The decisions worth testing are not here. `HandoverRule` holds the two
/// refusals and the order they are read in, and `ShareCache` holds where a file
/// is written and how it is swept. Both run on a laptop with no device in
/// sight.
///
/// **Text is handed over as text.** A `String` activity item goes straight into
/// whatever the operator picks, and nothing is written.
///
/// **A file is written once, app-private, and swept.** `OfferFile` writes the
/// bytes into `ShareCache`'s one directory in this app's own caches and hands
/// the sheet that file's URL. Nothing here learns when the chosen app is done
/// with it, so the directory is emptied before every new handover and on the
/// next launch: at most one file is ever in it.
///
/// **What the operator chose is never asked for.** `completionWithItemsHandler`
/// reports the activity type that was picked, and it is deliberately not set:
/// reporting which app received a diagnostic report would be this application
/// learning something about the operator it has no reason to know.
///
/// **Nothing handed over reaches a log line.** Not the title, not the file's
/// name, not a byte of the text or the file. The one line here carries the
/// outcome word.
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
    private static func present(title: String, item: Any) -> Bool {
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

            let sheet = UIActivityViewController(activityItems: [item], applicationActivities: nil)
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

    /// What the rule decided, as the answer the bridge hands back.
    private static func answer(_ rule: HandoverRule) -> [String: Any] {
        guard let why = rule.whyNot else {
            NSLog("%@ handover: %@", tag, offered)

            return Envelope.of(offered).asAnswer()
        }

        NSLog("%@ handover: refused, %@", tag, why.word)

        return Envelope.refusing(refused, why.word).asAnswer()
    }

    /// Put a report in front of whoever the operator picks.
    class Offer: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let title = parameters["title"] as? String ?? ""
            let text = parameters["text"] as? String ?? ""

            return answer(
                HandoverRule(
                    thereIsSomethingToHandOver: !text.isEmpty,
                    theSheetWasPresented: !text.isEmpty && present(title: title, item: text)
                ))
        }
    }

    /// Write a file into the share cache and put it in front of whoever the
    /// operator picks.
    ///
    /// The bytes arrive as base64, because the bridge carries JSON. Bytes that
    /// do not decode, a name that is not one file's name, and a file that could
    /// not be written are all *nothing to hand over*: the sheet is never asked.
    class OfferFile: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let title = parameters["title"] as? String ?? ""
            let name = parameters["name"] as? String ?? ""
            let bytes = Data(base64Encoded: parameters["bytes"] as? String ?? "") ?? Data()

            guard let cache = ShareCache.inThisAppsCaches(), let file = cache.write(name: name, bytes: bytes)
            else {
                return answer(HandoverRule(thereIsSomethingToHandOver: false, theSheetWasPresented: false))
            }

            return answer(
                HandoverRule(
                    thereIsSomethingToHandOver: true,
                    theSheetWasPresented: cache.holds(file) && present(title: title, item: file)
                ))
        }
    }
}
