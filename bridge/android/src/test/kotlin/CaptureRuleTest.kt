package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * The one decision the Android half makes, held to both requirements that want it.
 *
 * The same six cases as `CaptureRuleTests.swift`, in the same order. Keeping
 * them aligned is the point: the failure mode these two files exist to catch is
 * the platforms quietly disagreeing about when a window is protected, and a
 * disagreement is only visible if the questions are the same.
 */
class CaptureRuleTest {
    @Test
    fun `a backgrounded app is protected, whatever it was showing`() {
        assertTrue(CaptureRule(concealed = false, foreground = false).mustProtect)
        assertTrue(CaptureRule(concealed = true, foreground = false).mustProtect)
    }

    @Test
    fun `a guarded screen is protected while it is in front of you`() {
        // The case a rule written as `concealed && !foreground` gets wrong, and
        // the reason that expression is not repeated at call sites. A screen
        // recording runs while the app is the thing you are looking at, so this
        // is exactly when the protection is needed and exactly when a careless
        // reading drops it.
        assertTrue(CaptureRule(concealed = true, foreground = true).mustProtect)
    }

    @Test
    fun `an ordinary screen in front of you is not protected`() {
        // The one state that must *not* protect. Without this the rule could
        // return `true` unconditionally, every other test here would still pass,
        // and a build that refuses every screenshot forever would ship.
        assertFalse(CaptureRule(concealed = false, foreground = true).mustProtect)
    }

    @Test
    fun `the app launches in front of somebody, showing nothing guarded`() {
        assertEquals(CaptureRule(concealed = false, foreground = true), CaptureRule.LAUNCHED)
        assertFalse(CaptureRule.LAUNCHED.mustProtect)
    }

    @Test
    fun `concealing survives the app going away and coming back`() {
        // The sequence that actually happens: a guarded screen is open, the
        // operator takes a call, and comes back. If `foregrounded()` cleared
        // concealment the screen would be recordable on return, with nothing on
        // screen to say so.
        val returned = CaptureRule.LAUNCHED.concealing().backgrounded().foregrounded()

        assertTrue(returned.concealed)
        assertTrue(returned.mustProtect)
    }

    @Test
    fun `leaving a guarded screen while backgrounded stays protected`() {
        // Revealing takes away the guarded screen's reason to protect and leaves
        // the task switcher's. The window must still be protected, because the
        // app is still in the task switcher.
        val away = CaptureRule.LAUNCHED.concealing().backgrounded().revealing()

        assertFalse(away.concealed)
        assertTrue(away.mustProtect)
    }
}
