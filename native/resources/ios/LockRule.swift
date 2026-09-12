import Foundation

/// Whether the app must be locked, and whether it may ask right now.
///
/// Two questions rather than one, and keeping them apart is the whole of
/// `N4-R19`. "Must the app be locked" is about time and about whether anybody
/// has authenticated yet. "May the app prompt" is about what the operator is in
/// the middle of — and a prompt raised over an action in flight is the failure
/// the requirement names, because the operator answers it to get rid of it
/// rather than because they meant to.
///
/// No Security, no LocalAuthentication, no UIKit. Everything here is arithmetic
/// on values a caller passes in, which is what lets the decision be tested on a
/// laptop rather than demonstrated on a handset.
public struct LockRule: Equatable, Sendable {
    /// Whether the device has authenticated somebody since the app started.
    ///
    /// False on a cold start, which `N4-R19` says must always require the
    /// device's own authentication — there is no grace period across a launch,
    /// because the app that was open before is not the app that is open now.
    public let everAuthenticated: Bool

    /// How long ago that was.
    ///
    /// Meaningless while `everAuthenticated` is false, and deliberately not an
    /// optional: an optional here invites `?? 0`, which reads as "just now" and
    /// unlocks a cold start.
    public let secondsSinceAuthenticated: Int

    /// How long the operator chose to allow before asking again.
    ///
    /// Configurable per `N4-R19`. Zero means ask on every resume, which is a
    /// legitimate choice and is why this is not clamped to a minimum.
    public let grace: Int

    /// Whether the operator is in the middle of something.
    ///
    /// A command sent to a stack, a pairing half finished. `N4-R19` refuses a
    /// prompt here: an operator interrupted mid-action answers to get rid of the
    /// dialog, which is not authentication, it is an obstacle.
    public let actionInFlight: Bool

    /// - Parameters:
    ///   - everAuthenticated: whether the device has authenticated since launch.
    ///   - secondsSinceAuthenticated: how long ago that was.
    ///   - grace: how long the operator allows before being asked again.
    ///   - actionInFlight: whether something is mid-flight.
    public init(
        everAuthenticated: Bool,
        secondsSinceAuthenticated: Int,
        grace: Int,
        actionInFlight: Bool
    ) {
        self.everAuthenticated = everAuthenticated
        self.secondsSinceAuthenticated = secondsSinceAuthenticated
        self.grace = grace
        self.actionInFlight = actionInFlight
    }

    /// The app must be locked.
    ///
    /// `>=` rather than `>`: a grace of sixty seconds means sixty seconds of
    /// grace, and the sixtieth second is the first one past it. With `>` a grace
    /// of zero would never lock, which is the configuration meaning "ask every
    /// time".
    public var mustLock: Bool {
        !everAuthenticated || secondsSinceAuthenticated >= grace
    }

    /// The app may raise the device's authentication prompt now.
    ///
    /// Never merely `mustLock`. A locked app with an action in flight stays
    /// locked and stays quiet — the lock screen is shown, and the prompt waits
    /// until the action has finished (`N4-R19`).
    public var mayPrompt: Bool {
        mustLock && !actionInFlight
    }

    /// What a cold start looks like: nobody authenticated, whatever the clock says.
    public static func coldStart(grace: Int) -> LockRule {
        LockRule(
            everAuthenticated: false,
            secondsSinceAuthenticated: 0,
            grace: grace,
            actionInFlight: false
        )
    }

    /// The same rule with the device having just authenticated somebody.
    public func authenticated() -> LockRule {
        LockRule(
            everAuthenticated: true,
            secondsSinceAuthenticated: 0,
            grace: grace,
            actionInFlight: actionInFlight
        )
    }

    /// The same rule, some seconds later.
    public func after(seconds: Int) -> LockRule {
        LockRule(
            everAuthenticated: everAuthenticated,
            secondsSinceAuthenticated: secondsSinceAuthenticated + seconds,
            grace: grace,
            actionInFlight: actionInFlight
        )
    }

    /// The same rule with the operator in the middle of something.
    public func doing() -> LockRule {
        LockRule(
            everAuthenticated: everAuthenticated,
            secondsSinceAuthenticated: secondsSinceAuthenticated,
            grace: grace,
            actionInFlight: true
        )
    }

    /// The same rule with that action finished.
    public func idle() -> LockRule {
        LockRule(
            everAuthenticated: everAuthenticated,
            secondsSinceAuthenticated: secondsSinceAuthenticated,
            grace: grace,
            actionInFlight: false
        )
    }
}
