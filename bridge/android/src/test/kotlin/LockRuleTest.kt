package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * When the app must be locked, and when it may ask.
 *
 * The same seven cases as `LockRuleTests.swift`, in the same order. Keeping them
 * aligned is the point: the failure these two files exist to catch is the
 * platforms quietly disagreeing, and a disagreement is only visible if the
 * questions are the same.
 */
class LockRuleTest {
    @Test
    fun `a cold start is locked, whatever the grace period says`() {
        // No grace across a launch. The app that was open before is not the app
        // that is open now, and an hour of grace configured yesterday must not
        // carry a relaunch today.
        assertTrue(LockRule.coldStart(grace = 3600).mustLock)
        assertTrue(LockRule.coldStart(grace = 0).mustLock)
    }

    @Test
    fun `an app idle past its grace period is locked`() {
        assertTrue(LockRule.coldStart(grace = 60).authenticated().after(seconds = 60).mustLock)
    }

    @Test
    fun `an app inside its grace period is not locked`() {
        // The one state that must *not* lock. Without it the rule could answer
        // true unconditionally, every other test here would still pass, and an
        // app demanding a passcode on every glance would ship.
        val rule = LockRule.coldStart(grace = 60).authenticated().after(seconds = 59)

        assertFalse(rule.mustLock)
        assertFalse(rule.mayPrompt)
    }

    @Test
    fun `a grace of nothing means asking every time`() {
        // `>=` rather than `>` is what makes this work, and it is the boundary
        // the comparison gets wrong in the other direction: with `>`, a grace of
        // zero would never lock at all — the configuration meaning "ask every
        // time" would mean "never ask".
        assertTrue(LockRule.coldStart(grace = 0).authenticated().mustLock)
    }

    @Test
    fun `no prompt while the operator is in the middle of something`() {
        // Locked, and silent. The lock screen is shown; the device's own prompt
        // waits. An operator interrupted mid-action answers a dialog to get rid
        // of it, which is not authentication.
        val rule = LockRule.coldStart(grace = 60).doing()

        assertTrue(rule.mustLock)
        assertFalse(rule.mayPrompt)
    }

    @Test
    fun `the prompt comes once the action is finished`() {
        val rule = LockRule.coldStart(grace = 60).doing().idle()

        assertTrue(rule.mustLock)
        assertTrue(rule.mayPrompt)
    }

    @Test
    fun `authenticating clears the lock and the clock`() {
        val rule = LockRule.coldStart(grace = 60).after(seconds = 500).authenticated()

        assertFalse(rule.mustLock)
        assertEquals(0, rule.secondsSinceAuthenticated)
    }
}
