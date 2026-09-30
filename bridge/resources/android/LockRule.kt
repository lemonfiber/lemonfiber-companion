package app.lemonfiber.native

/**
 * Whether the app is locked, and what the glass may show.
 *
 * The lock stands from a cold start until the device's own prompt succeeds.
 * Leaving the app starts a clock, and coming back once [after] seconds have
 * passed stands it again. Nothing else opens it except [waived], which the PHP
 * half asks for only where the store holds nothing for the lock to guard.
 *
 * Time is whatever monotonic clock the caller reads, counting while the phone
 * sleeps, so setting the phone's clock back cannot shorten a time away.
 *
 * No Android framework here: every answer is arithmetic on values a caller
 * passes in, and `LockRule.swift` answers the same questions line for line.
 */
public data class LockRule(
    /** Whether the lock stands. */
    public val held: Boolean,
    /** How many seconds the app may be away before the lock stands again. */
    public val after: Long,
    /** Whether the app is out of sight right now. */
    public val away: Boolean,
    /** When the app last went out of sight, in the caller's monotonic seconds. */
    public val leftAt: Long,
    /** Whether the device's own prompt is up. */
    public val prompting: Boolean,
    /** Whether the glass stays covered until the lock screen is drawn on it. */
    public val hiding: Boolean,
    /** Whether the prompt was raised without being asked for, for this standing. */
    public val asked: Boolean,
) {
    /**
     * Whether the lock stands at [now], counting a time away that has not ended.
     *
     * A notification shown while the app is away asks this, because the lock
     * stands from the moment the time away passes [after], not from the return.
     * A device with no screen lock of its own has nobody to ask, and there the
     * lock never stands.
     */
    public fun standsAt(
        now: Long,
        canAsk: Boolean,
    ): Boolean = canAsk && (held || (away && now - leftAt >= after))

    /** Whether the glass must show nothing of the app. */
    public val mustCover: Boolean
        get() = away || hiding

    /**
     * Whether the prompt may go up without the operator asking for it.
     *
     * Once per standing. An operator who dismissed the prompt meant it, and the
     * lock screen offers the prompt again on a tap.
     */
    public val mayAskByItself: Boolean
        get() = held && !asked && !prompting

    /**
     * The app went out of sight at [now].
     *
     * Not while the device's own prompt is up: on some devices the passcode
     * screen is an activity of its own, and counting it as leaving would stand
     * the lock again over the answer that opened it.
     */
    public fun left(now: Long): LockRule = if (prompting) this else copy(away = true, leftAt = now)

    /**
     * The app came back into sight at [now], on a device that [canAsk] or not.
     *
     * `>=` rather than `>`, so that a Lock after of nothing stands the lock on
     * every return. A lock that stands afresh hides the glass until the lock
     * screen is on it, and may ask once more by itself.
     */
    public fun returned(
        now: Long,
        canAsk: Boolean,
    ): LockRule {
        if (prompting || !away) {
            return copy(away = false)
        }

        val stands = canAsk && (held || now - leftAt >= after)
        val afresh = stands && !held

        return copy(
            held = stands,
            away = false,
            hiding = hiding || afresh,
            asked = asked && !afresh,
        )
    }

    /** The lock screen is on the glass, so the glass need not be covered. */
    public fun drawn(): LockRule = copy(hiding = false)

    /** The device's own prompt went up. */
    public fun asking(): LockRule = copy(prompting = true)

    /** The prompt went up without the operator asking for it. */
    public fun askingByItself(): LockRule = copy(prompting = true, asked = true)

    /**
     * The device's own prompt answered.
     *
     * Only [succeeded] opens the lock. A failure, a cancel, a lockout and an
     * error all leave it exactly as it was.
     */
    public fun answered(succeeded: Boolean): LockRule =
        copy(
            held = held && !succeeded,
            hiding = hiding && !succeeded,
            prompting = false,
        )

    /** The store holds nothing the lock guards, so it stands down. */
    public fun waived(): LockRule = copy(held = false, hiding = false)

    /** The operator chose how long the app may be away. */
    public fun awayFor(seconds: Long): LockRule = copy(after = seconds)

    /** Where the starting state lives, named so it reads at a call site. */
    public companion object {
        /** A cold start: the lock stands, and Lock after is immediately until told otherwise. */
        public fun coldStart(): LockRule =
            LockRule(
                held = true,
                after = 0,
                away = false,
                leftAt = 0,
                prompting = false,
                hiding = false,
                asked = false,
            )
    }
}
