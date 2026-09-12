package app.lemonfiber.native

/**
 * Whether the app must be locked, and whether it may ask right now.
 *
 * Two questions rather than one, and keeping them apart is the whole of
 * `N4-R19`. "Must the app be locked" is about time and about whether anybody has
 * authenticated yet. "May the app prompt" is about what the operator is in the
 * middle of — and a prompt raised over an action in flight is the failure the
 * requirement names, because the operator answers it to get rid of it rather
 * than because they meant to.
 *
 * No Android framework here. Everything is arithmetic on values a caller passes
 * in, which is what lets the decision be tested on a JVM in two seconds rather
 * than demonstrated on a handset.
 *
 * Deliberately mirrors `LockRule.swift` line for line, for the reason the
 * capture rule is mirrored: two platforms disagreeing about when an app locks is
 * a bug nobody finds, because each half looks right on its own.
 */
public data class LockRule(
    /**
     * Whether the device has authenticated somebody since the app started.
     *
     * False on a cold start, which `N4-R19` says must always require the
     * device's own authentication — there is no grace period across a launch,
     * because the app that was open before is not the app that is open now.
     */
    public val everAuthenticated: Boolean,
    /**
     * How long ago that was.
     *
     * Meaningless while [everAuthenticated] is false, and deliberately not
     * nullable: a null here invites `?: 0`, which reads as "just now" and
     * unlocks a cold start.
     */
    public val secondsSinceAuthenticated: Int,
    /**
     * How long the operator chose to allow before being asked again.
     *
     * Configurable per `N4-R19`. Zero means ask on every resume, which is a
     * legitimate choice and is why this is not clamped to a minimum.
     */
    public val grace: Int,
    /**
     * Whether the operator is in the middle of something.
     *
     * A command sent to a stack, a pairing half finished. `N4-R19` refuses a
     * prompt here: an operator interrupted mid-action answers to get rid of the
     * dialog, which is not authentication, it is an obstacle.
     */
    public val actionInFlight: Boolean,
) {
    /**
     * The app must be locked.
     *
     * `>=` rather than `>`: a grace of sixty seconds means sixty seconds of
     * grace, and the sixtieth second is the first one past it. With `>` a grace
     * of zero would never lock, which is the configuration meaning "ask every
     * time".
     */
    public val mustLock: Boolean
        get() = !everAuthenticated || secondsSinceAuthenticated >= grace

    /**
     * The app may raise the device's authentication prompt now.
     *
     * Never merely [mustLock]. A locked app with an action in flight stays
     * locked and stays quiet — the lock screen is shown, and the prompt waits
     * until the action has finished (`N4-R19`).
     */
    public val mayPrompt: Boolean
        get() = mustLock && !actionInFlight

    /** The same rule with the device having just authenticated somebody. */
    public fun authenticated(): LockRule = copy(everAuthenticated = true, secondsSinceAuthenticated = 0)

    /** The same rule, some seconds later. */
    public fun after(seconds: Int): LockRule =
        copy(secondsSinceAuthenticated = secondsSinceAuthenticated + seconds)

    /** The same rule with the operator in the middle of something. */
    public fun doing(): LockRule = copy(actionInFlight = true)

    /** The same rule with that action finished. */
    public fun idle(): LockRule = copy(actionInFlight = false)

    /** Where the starting state lives, named so it reads at a call site. */
    public companion object {
        /** What a cold start looks like: nobody authenticated, whatever the clock says. */
        public fun coldStart(grace: Int): LockRule =
            LockRule(
                everAuthenticated = false,
                secondsSinceAuthenticated = 0,
                grace = grace,
                actionInFlight = false,
            )
    }
}
