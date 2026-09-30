import Foundation

/// Whether the app is locked, and what the glass may show.
///
/// The lock stands from a cold start until the device's own prompt succeeds.
/// Leaving the app starts a clock, and coming back once `after` seconds have
/// passed stands it again. Nothing else opens it except `waived()`, which the
/// PHP half asks for only where the store holds nothing for the lock to guard.
///
/// Time is whatever monotonic clock the caller reads, counting while the phone
/// sleeps, so setting the phone's clock back cannot shorten a time away.
///
/// No UIKit and no LocalAuthentication here: every answer is arithmetic on
/// values a caller passes in, and `LockRule.kt` answers the same questions line
/// for line.
public struct LockRule: Equatable, Sendable {
    /// Whether the lock stands.
    public let held: Bool

    /// How many seconds the app may be away before the lock stands again.
    public let after: Int64

    /// Whether the app is out of sight right now.
    public let away: Bool

    /// When the app last went out of sight, in the caller's monotonic seconds.
    public let leftAt: Int64

    /// Whether the device's own prompt is up.
    public let prompting: Bool

    /// Whether the glass stays covered until the lock screen is drawn on it.
    public let hiding: Bool

    /// Whether the prompt was raised without being asked for, for this standing.
    public let asked: Bool

    /// A cold start: the lock stands, and Lock after is immediately until told otherwise.
    public static func coldStart() -> LockRule {
        LockRule(
            held: true,
            after: 0,
            away: false,
            leftAt: 0,
            prompting: false,
            hiding: false,
            asked: false
        )
    }

    /// Whether the lock stands at `now`, counting a time away that has not ended.
    ///
    /// A notification shown while the app is away asks this, because the lock
    /// stands from the moment the time away passes `after`, not from the return.
    /// A device with no screen lock of its own has nobody to ask, and there the
    /// lock never stands.
    public func standsAt(now: Int64, canAsk: Bool) -> Bool {
        canAsk && (held || (away && now - leftAt >= after))
    }

    /// Whether the glass must show nothing of the app.
    public var mustCover: Bool {
        away || hiding
    }

    /// Whether the prompt may go up without the operator asking for it.
    ///
    /// Once per standing. An operator who dismissed the prompt meant it, and the
    /// lock screen offers the prompt again on a tap.
    public var mayAskByItself: Bool {
        held && !asked && !prompting
    }

    /// The app went out of sight at `now`.
    ///
    /// Not while the device's own prompt is up: the passcode sheet takes the app
    /// out of the active state, and counting it as leaving would stand the lock
    /// again over the answer that opened it.
    public func left(_ now: Int64) -> LockRule {
        prompting ? self : with(away: true, leftAt: now)
    }

    /// The app came back into sight at `now`, on a device that `canAsk` or not.
    ///
    /// `>=` rather than `>`, so that a Lock after of nothing stands the lock on
    /// every return. A lock that stands afresh hides the glass until the lock
    /// screen is on it, and may ask once more by itself.
    public func returned(now: Int64, canAsk: Bool) -> LockRule {
        if prompting || !away {
            return with(away: false)
        }

        let stands = canAsk && (held || now - leftAt >= after)
        let afresh = stands && !held

        return with(held: stands, away: false, hiding: hiding || afresh, asked: asked && !afresh)
    }

    /// The lock screen is on the glass, so the glass need not be covered.
    public func drawn() -> LockRule {
        with(hiding: false)
    }

    /// The device's own prompt went up.
    public func asking() -> LockRule {
        with(prompting: true)
    }

    /// The prompt went up without the operator asking for it.
    public func askingByItself() -> LockRule {
        with(prompting: true, asked: true)
    }

    /// The device's own prompt answered.
    ///
    /// Only `succeeded` opens the lock. A failure, a cancel, a lockout and an
    /// error all leave it exactly as it was.
    public func answered(succeeded: Bool) -> LockRule {
        with(held: held && !succeeded, prompting: false, hiding: hiding && !succeeded)
    }

    /// The store holds nothing the lock guards, so it stands down.
    public func waived() -> LockRule {
        with(held: false, hiding: false)
    }

    /// The operator chose how long the app may be away.
    public func awayFor(seconds: Int64) -> LockRule {
        with(after: seconds)
    }

    /// The same rule with the named facts changed, which is Kotlin's `copy`.
    private func with(
        held: Bool? = nil,
        after: Int64? = nil,
        away: Bool? = nil,
        leftAt: Int64? = nil,
        prompting: Bool? = nil,
        hiding: Bool? = nil,
        asked: Bool? = nil
    ) -> LockRule {
        LockRule(
            held: held ?? self.held,
            after: after ?? self.after,
            away: away ?? self.away,
            leftAt: leftAt ?? self.leftAt,
            prompting: prompting ?? self.prompting,
            hiding: hiding ?? self.hiding,
            asked: asked ?? self.asked
        )
    }
}
