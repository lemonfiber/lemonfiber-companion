import Foundation
import LocalAuthentication

/// The device's own authentication, on iOS.
///
/// Namespace: `Lemonfiber.Authenticate`, `Lemonfiber.CanAuthenticate`
///
/// **Why this exists rather than the stock biometric call.** A biometric failure
/// falls back to the device's passcode and must never fall back to unlocked.
/// That is `WhatUnlocks.accepted`, chosen before the sheet is shown: with the
/// biometrics-only policy an operator with a passcode and no enrolled face, or
/// whose face was removed, could not get in at all.
///
/// **The answer is the platform's.** `Authenticate` waits for the sheet and
/// answers `authenticated: true` only from `LAContext`'s success, which is also
/// the one place `TheLock` opens.
public enum LemonfiberAuth {
    /// What the sheet is captioned with where a caller sends no reason.
    private static let unlock = "Unlock lemonfiber"

    /// `Lemonfiber.Authenticate` — ask the device who this is, and wait for it.
    ///
    /// The bridge thread waits while the operator answers, as it does for the
    /// camera and the notification prompt, so what comes back is what they did.
    public class Authenticate: BridgeFunction {
        /// Raise the device's own prompt, and answer what the operator did.
        public func execute(parameters: [String: Any]) throws -> [String: Any] {
            let reason = parameters["reason"] as? String ?? unlock

            return BridgeResponse.success(data: ["authenticated": TheLock.promptAndWait(reason: reason)])
        }
    }

    /// `Lemonfiber.CanAuthenticate` — whether the device can ask at all.
    ///
    /// A device with no passcode set cannot authenticate anybody, and that is a
    /// different condition from somebody declining: one is answered by telling
    /// the operator to set a passcode, the other by asking again.
    public class CanAuthenticate: BridgeFunction {
        /// Whether a screen lock is configured, so there is anybody to ask.
        public func execute(parameters: [String: Any]) throws -> [String: Any] {
            BridgeResponse.success(
                data: ["canAuthenticate": LAContext().canEvaluatePolicy(WhatUnlocks.accepted, error: nil)]
            )
        }
    }
}
