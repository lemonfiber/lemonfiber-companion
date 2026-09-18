import AVFoundation
import Foundation
import UIKit

/// Reading a pairing code off a screen, on iOS.
///
/// Namespace: `Lemonfiber.Scanning.*`
///
/// The decision worth testing is not in this file. `CameraRule` holds what the
/// platform's facts mean together — a missing lens told from a refused one, and
/// a refusal that can be put again told from one that has been settled — and it
/// runs on a laptop with no device in sight. What is here is the part that
/// cannot be tested off a phone: reading those facts, raising the prompt, and
/// putting the camera on screen.
///
/// **The code that was read comes back in the answer to this call, and nowhere
/// else.** Not on an event. Here an event is a write into the PHP queue and
/// nothing more, so the two channels are equally private on this platform — but
/// they are not on the other, where an event is also injected into the WebView
/// as a DOM event, a Livewire dispatch and an HTTP POST. The narrow road is the
/// one both halves take, because a capability that is careful on one platform
/// and not the other is a capability nobody can reason about.
///
/// **Nothing read is logged, at any level.** Not truncated, not hashed, not the
/// first characters. The log lines here carry closed words and nothing that came
/// off a camera, which is what makes the promise checkable by reading the file.
///
/// **Everything here is synchronous over an asynchronous platform**, for the
/// reason telling somebody is: a bridge function answers a value, and the camera
/// answers a delegate callback, so the callback is waited on.
enum ScanningFunctions {
    /// Whether the camera prompt has ever been raised by this application.
    private static let everAsked = "lemonfiber.scanning.ever_asked"

    /// How long the operator is given to answer the prompt.
    private static let patienceWithAPerson: TimeInterval = 120

    /// How long the scanner is left open before it is treated as closed.
    ///
    /// Bounded because an unbounded wait is a thread this application never
    /// gets back: a scanner left open on a bench would hold the call for ever.
    /// Ten minutes is far longer than reading a code takes and far shorter than
    /// a working day.
    private static let patienceWithAScan: TimeInterval = 600

    /// The capability's word for having read something.
    private static let read = "read"

    /// The capability's word for having read nothing.
    private static let nothing = "nothing"

    /// What the platform reports, gathered into the four facts the rule reads.
    ///
    /// iOS answers the permission question directly — `AVAuthorizationStatus`
    /// has a `.notDetermined` of its own — so nothing has to be reconstructed.
    /// The rule is asked anyway, with the same four facts, because a rule that
    /// exists on one platform only cannot be seen to disagree with the other.
    ///
    /// `wouldExplain` is always false: this platform shows the camera prompt
    /// once in the life of an install and has nothing that says an explanation
    /// would help. That is the fact behind `mayAskAgain` being false here after
    /// any refusal, and it is why a refused camera sends the operator to
    /// Settings rather than offering another go.
    private static func rule() -> CameraRule {
        let status = AVCaptureDevice.authorizationStatus(for: .video)

        return CameraRule(
            cameraExists: AVCaptureDevice.default(for: .video) != nil,
            permissionIsGranted: status == .authorized,
            wouldExplain: false,
            everAsked: UserDefaults.standard.bool(forKey: everAsked) || status != .notDetermined
        )
    }

    /// Raise the camera prompt and wait for the operator to answer it.
    ///
    /// The record is written before the prompt goes up rather than after it is
    /// answered, because a process killed while the prompt is on screen has
    /// still asked.
    private static func ask() {
        UserDefaults.standard.set(true, forKey: everAsked)

        let waiting = DispatchSemaphore(value: 0)

        AVCaptureDevice.requestAccess(for: .video) { _ in waiting.signal() }

        _ = waiting.wait(timeout: .now() + patienceWithAPerson)
    }

    /// Put the camera on screen and wait for it to come off again.
    ///
    /// - Parameter prompt: what the operator is told the camera is for.
    /// - Returns: the code that was read, or nil where the screen closed
    ///   without one.
    private static func scan(prompt: String) -> String? {
        let waiting = DispatchSemaphore(value: 0)
        var found: String?

        DispatchQueue.main.async {
            guard let top = topmost() else {
                waiting.signal()

                return
            }

            let screen = ScanningViewController(prompt: prompt) { code in
                found = code
                waiting.signal()
            }

            screen.modalPresentationStyle = .fullScreen
            top.present(screen, animated: true)
        }

        _ = waiting.wait(timeout: .now() + patienceWithAScan)

        return found
    }

    /// Whatever is in front of the operator right now.
    ///
    /// Walked rather than remembered, because what is on top changes while this
    /// application runs and a reference kept at launch would present the
    /// scanner underneath whatever came after it.
    @MainActor
    private static func topmost() -> UIViewController? {
        let scenes = UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }
        let windows = scenes.flatMap(\.windows)

        var top = windows.first(where: \.isKeyWindow)?.rootViewController

        while let next = top?.presentedViewController {
            top = next
        }

        return top
    }

    /// The answer where the camera will not open at all.
    ///
    /// The fallback reason is reachable rather than defensive: a prompt the
    /// operator walks away from leaves the permission undecided, the wait above
    /// gives up after two minutes, and what comes back is a rule that still says
    /// *ask me*. Nothing was read and nobody refused, and *not permitted* with
    /// `may_ask_again` true is the honest reading of that.
    private static func refused(_ settled: CameraRule) -> [String: Any] {
        let why = settled.whyNot ?? .theCameraIsNotPermitted

        NSLog("Lemonfiber scanning: nothing read, %@", why.word)

        return Envelope.refusing(nothing, because: why.word, mayAskAgain: settled.mayAskAgain).asAnswer()
    }

    /// The answer once the scanner has closed.
    private static func whatWasRead(_ code: String?) -> [String: Any] {
        guard let code, !code.isEmpty else {
            NSLog("Lemonfiber scanning: nothing read, the scanner closed")

            let closed = Envelope.refusing(
                nothing,
                because: WhyNothingWasRead.theOperatorClosedIt.word,
                mayAskAgain: true
            )

            return closed.asAnswer()
        }

        NSLog("Lemonfiber scanning: a code was read")

        return Envelope.of(read, carrying: ["payload": code]).asAnswer()
    }

    /// `Lemonfiber.Scanning.Read` — open the camera and read one pairing code.
    ///
    /// Blocks until the scanner closes, which is what lets the code come back in
    /// the answer rather than on an event.
    class Read: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let prompt = parameters["prompt"] as? String ?? ""

            if rule().mayAsk {
                ask()
            }

            let settled = rule()

            return BridgeResponse.success(
                data: settled.mayOpen ? whatWasRead(scan(prompt: prompt)) : refused(settled)
            )
        }
    }
}
