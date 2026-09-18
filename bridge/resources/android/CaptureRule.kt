package app.lemonfiber.native

/**
 * Whether the window must be protected from capture right now.
 *
 * Two requirements want the same window flag for different reasons, and they
 * want it at different times. Keeping the decision here — with no Android
 * framework in sight — is what makes it a thing that can be tested rather than
 * a thing that has to be demonstrated on a handset.
 *
 * **The task switcher.** What the app shows there must be no application
 * content at all. The platform takes that snapshot as the app leaves the
 * foreground, so the protection is needed *while backgrounded* and not before.
 * Holding it permanently would also refuse every deliberate screenshot, which
 * neither rule asks for and which an operator sending a support screenshot
 * would resent.
 *
 * **A screen holding a secret.** One showing a credential, a session token or
 * pairing material is kept out of that snapshot *and* out of a screen
 * recording. A recording runs while the app is in front of you, so this one is
 * needed in the foreground too, and that is why `concealed` is a separate fact
 * rather than one derived from `foreground`.
 *
 * Deliberately mirrors `CaptureRule.swift` line for line. Two platforms
 * disagreeing about when a window is protected is the bug nobody finds, because
 * each half looks right on its own.
 */
public data class CaptureRule(
    /** Whether a screen declaring `#[Concealed]` is on top. */
    public val concealed: Boolean,
    /** Whether the app is the thing the operator is looking at. */
    public val foreground: Boolean,
) {
    /**
     * The window must be protected.
     *
     * Deliberately not `concealed || !foreground` written at each call site. The
     * expression is short enough to retype and that is the problem: the version
     * somebody retypes is `concealed && !foreground`, which protects nothing
     * that matters and survives a casual read.
     */
    public val mustProtect: Boolean
        get() = concealed || !foreground

    /** The same rule with the app moved to the background. */
    public fun backgrounded(): CaptureRule = copy(foreground = false)

    /** The same rule with the app brought back to the front. */
    public fun foregrounded(): CaptureRule = copy(foreground = true)

    /** The same rule with a guarded screen on top. */
    public fun concealing(): CaptureRule = copy(concealed = true)

    /** The same rule with that screen gone. */
    public fun revealing(): CaptureRule = copy(concealed = false)

    /** Where the starting state lives, named so it reads at a call site. */
    public companion object {
        /** What the app starts as: in front of somebody, showing nothing guarded. */
        public val LAUNCHED: CaptureRule = CaptureRule(concealed = false, foreground = true)
    }
}
