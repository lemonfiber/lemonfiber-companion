import Foundation
import UIKit

/// The iOS half of lemonfiber's own native expansion.
///
/// Namespace: `Lemonfiber.*`
///
/// The decision about *when* to protect the window lives in `CaptureRule`, which
/// imports nothing from UIKit and is unit-tested. This file is what cannot be
/// tested off a device, and is kept down to the calls that need one.
///
/// **iOS has no `FLAG_SECURE`.** Android hands you one window flag that answers
/// both requirements; here they are two different mechanisms and it is worth
/// being clear which is which.
///
/// **The task-switcher snapshot** is answered by covering the window as the
/// app resigns active. The system takes its snapshot after that
/// notification, so a view added there is what ends up in the switcher. This
/// is reliable and is what every banking app does.
///
/// **Screen recording and screenshots** have no complete answer on iOS, and
/// pretending otherwise would be the dangerous thing to do. `UIScreen.isCaptured`
/// reports that a recording or mirroring is running, and this covers the window
/// while it is true, which stops a guarded screen reaching the recording. A
/// *screenshot* cannot be blocked at all; `userDidTakeScreenshotNotification`
/// arrives after the fact. So on iOS a guarded screen is protected from recording
/// and from the switcher, and a deliberate screenshot by the person holding the
/// phone is out of reach. That is a platform limit, not a gap in this file, and
/// it is written here so nobody has to rediscover it.
enum LemonfiberFunctions {
    /// What the app is showing and whether anybody is looking at it.
    ///
    /// Process-wide because the window is, and guarded by the main queue because
    /// every read and write happens there.
    nonisolated(unsafe) private static var rule: CaptureRule = .launched

    /// The view put over the window. One instance, reused.
    nonisolated(unsafe) private static var cover: UIView?

    /// Start watching the app move in and out of the foreground.
    ///
    /// Called once from `LemonfiberInit`. Without it the window is never covered,
    /// and the failure is invisible: every screen looks right, and the task
    /// switcher quietly holds the last frame.
    static func install() {
        let centre = NotificationCenter.default

        centre.addObserver(
            forName: UIApplication.willResignActiveNotification,
            object: nil,
            queue: .main
        ) { _ in
            rule = rule.backgrounded()
            apply()
        }

        centre.addObserver(
            forName: UIApplication.didBecomeActiveNotification,
            object: nil,
            queue: .main
        ) { _ in
            rule = rule.foregrounded()
            apply()
        }

        // A recording can start while the app is in front of you, which is the
        // case a guarded screen is about and the one a backgrounding observer
        // never sees.
        centre.addObserver(
            forName: UIScreen.capturedDidChangeNotification,
            object: nil,
            queue: .main
        ) { _ in
            apply()
        }
    }

    /// Put the window into whatever state the rule now calls for.
    ///
    /// `isCaptured` is read here rather than folded into `CaptureRule`, because
    /// it is a fact about the device at this instant rather than about what the
    /// app is showing — and the rule is the part that is meant to be testable
    /// without one.
    private static func apply() {
        let recording = UIScreen.main.isCaptured
        let hide = rule.mustProtect || (rule.concealed && recording)

        guard
            let window = UIApplication.shared.connectedScenes
                .compactMap({ $0 as? UIWindowScene })
                .flatMap({ $0.windows })
                .first(where: { $0.isKeyWindow })
        else {
            return
        }

        if hide {
            let over = cover ?? makeCover()
            cover = over
            over.frame = window.bounds
            window.addSubview(over)
            window.bringSubviewToFront(over)
        } else {
            cover?.removeFromSuperview()
        }
    }

    /// The thing the task switcher gets a picture of.
    ///
    /// Deliberately opaque and deliberately empty. A blur over the real view is
    /// the prettier choice and it is a picture of the content: a heavy blur of a
    /// credential is still shaped like one, and the switcher's thumbnail is small
    /// enough that "shaped like" is most of what survives anyway.
    private static func makeCover() -> UIView {
        let over = UIView()
        over.backgroundColor = .systemBackground
        over.autoresizingMask = [.flexibleWidth, .flexibleHeight]

        return over
    }

    /// `Lemonfiber.Conceal` — a screen holding a secret has come up.
    class Conceal: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            DispatchQueue.main.async {
                rule = rule.concealing()
                apply()
            }

            return BridgeResponse.success(data: ["protected": true])
        }
    }

    /// `Lemonfiber.Reveal` — that screen has gone.
    class Reveal: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            DispatchQueue.main.async {
                rule = rule.revealing()
                apply()
            }

            return BridgeResponse.success(data: ["protected": false])
        }
    }

    /// `Lemonfiber.IsProtected` — whether the window is protected right now.
    ///
    /// Answered from the rule rather than by asking whether the cover is in the
    /// hierarchy. The cover is added on the main queue and this call arrives from
    /// the bridge, so a read of the view tree can beat its own write and report
    /// the previous state as the current one.
    class IsProtected: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            BridgeResponse.success(data: ["protected": rule.mustProtect])
        }
    }

    /// `Lemonfiber.IsInFront`: whether the app is in front of somebody right now.
    ///
    /// The rule's own record of the last resign or become-active, which is
    /// what the observers installed at launch wrote.
    class IsInFront: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            BridgeResponse.success(data: ["inFront": rule.foreground])
        }
    }
}
