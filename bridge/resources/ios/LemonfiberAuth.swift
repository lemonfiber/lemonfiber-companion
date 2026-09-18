import Foundation
import LocalAuthentication

/// The device's own authentication, on iOS.
///
/// Namespace: `Lemonfiber.Authenticate`
///
/// **Why this exists rather than the stock biometric call.** A biometric failure
/// falls back to the device's passcode and must never fall back to unlocked. On
/// iOS that is one enum case chosen before the dialog is shown, and the two
/// differ in exactly the way that matters:
///
/// - `.deviceOwnerAuthenticationWithBiometrics` is Face ID or Touch ID **only**.
///   A device with no biometry enrolled fails `canEvaluatePolicy` outright, and
///   a face that will not scan has nowhere to go. The operator is locked out of
///   their own app.
/// - `.deviceOwnerAuthentication` is biometry **with the passcode behind it**.
///   The system offers the passcode when biometry fails or is unavailable, and
///   handles the fallback itself.
///
/// The second is the one that provides the fallback, and it is what this uses.
/// Every published example of this API reaches for the first, which is why the
/// choice is written down here rather than left to look like a detail.
///
/// **There is no third answer.** Every path answers `authenticated: true` or
/// `false`, and every error is false. The second clause — must never fall back
/// to unlocked — is kept by there being nowhere in this file that answers true
/// without `LAContext` having said so.
public enum LemonfiberAuth {
    /// What this app accepts as the device's own authentication.
    ///
    /// That fallback in one enum case. See the type's own documentation for why
    /// the biometrics-only policy is the wrong one.
    private static let accepted: LAPolicy = .deviceOwnerAuthentication

    /// `Lemonfiber.Authenticate` — ask the device who this is.
    ///
    /// Answers `acknowledged` immediately and sends the real result as an event,
    /// because the dialog is the operator's to answer in their own time and a
    /// bridge call that waited would hold the thread it was called on.
    public class Authenticate: BridgeFunction {
        /// Raise the device's own prompt, and report the answer as an event.
        public func execute(parameters: [String: Any]) throws -> [String: Any] {
            let reason = parameters["reason"] as? String ?? "Unlock lemonfiber"

            // A fresh context per prompt. `LAContext` caches a successful
            // evaluation for the life of the object, so a reused one can answer
            // "yes" to a second question nobody was asked — which is precisely a
            // fall to unlocked.
            let context = LAContext()

            context.evaluatePolicy(accepted, localizedReason: reason) { success, _ in
                DispatchQueue.main.async {
                    LaravelBridge.shared.send?(
                        "Lemonfiber\\Native\\Events\\Authenticated",
                        ["authenticated": success]
                    )
                }
            }

            return BridgeResponse.success(data: ["acknowledged": true])
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
            var problem: NSError?
            let can = LAContext().canEvaluatePolicy(accepted, error: &problem)

            return BridgeResponse.success(data: ["canAuthenticate": can])
        }
    }
}
