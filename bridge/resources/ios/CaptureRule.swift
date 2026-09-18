import Foundation

/// Whether the window must be protected from capture right now.
///
/// Two requirements want the same window flag for different reasons, and they
/// want it at different times. Keeping the decision here — with no UIKit in
/// sight — is what makes it a thing that can be tested rather than a thing that
/// has to be demonstrated on a handset.
///
/// **The task switcher.** What the app shows there must be no application
/// content at all. The platform takes that snapshot as the app leaves the
/// foreground, so the protection is needed *while backgrounded* and is not
/// needed before. Holding it permanently would also refuse every deliberate
/// screenshot, which neither rule asks for and which an operator wanting to
/// send a support screenshot would resent.
///
/// **A screen holding a secret.** One showing a credential, a session token or
/// pairing material is kept out of that snapshot *and* out of a screen
/// recording. A recording runs while the app is in front of you, so this one is
/// needed in the foreground too, and it is why `concealed` is separate from
/// `foreground` rather than derived from it.
public struct CaptureRule: Equatable, Sendable {
    /// Whether a screen declaring `#[Concealed]` is on top.
    public let concealed: Bool

    /// Whether the app is the thing the operator is looking at.
    public let foreground: Bool

    /// - Parameters:
    ///   - concealed: whether a screen declaring `#[Concealed]` is on top.
    ///   - foreground: whether the app is the thing the operator is looking at.
    public init(concealed: Bool, foreground: Bool) {
        self.concealed = concealed
        self.foreground = foreground
    }

    /// The window must be protected.
    ///
    /// Deliberately not `concealed || !foreground` written at each call site.
    /// The expression is short enough to retype and that is the problem: the
    /// version somebody retypes is `concealed && !foreground`, which protects
    /// nothing that matters and passes a casual read.
    public var mustProtect: Bool {
        concealed || !foreground
    }

    /// The same rule with the app moved to the background.
    public func backgrounded() -> CaptureRule {
        CaptureRule(concealed: concealed, foreground: false)
    }

    /// The same rule with the app brought back to the front.
    public func foregrounded() -> CaptureRule {
        CaptureRule(concealed: concealed, foreground: true)
    }

    /// The same rule with a guarded screen on top.
    public func concealing() -> CaptureRule {
        CaptureRule(concealed: true, foreground: foreground)
    }

    /// The same rule with that screen gone.
    public func revealing() -> CaptureRule {
        CaptureRule(concealed: false, foreground: foreground)
    }

    /// What the app starts as: in front of somebody, showing nothing guarded.
    public static let launched = CaptureRule(concealed: false, foreground: true)
}
