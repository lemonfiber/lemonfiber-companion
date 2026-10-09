package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * Who may connect to the player.
 *
 * The same cases as `ControllerRuleTests.swift`, in the same order.
 */
class ControllerRuleTest {
    private val theApp = "app.lemonfiber.companion"

    @Test
    fun `this app connects to its own player`() {
        assertTrue(ControllerRule.admits(theApp, theApp, holdsMediaControl = false))
    }

    @Test
    fun `the system connects, by the media control permission it alone holds`() {
        assertTrue(ControllerRule.admits("com.android.systemui", theApp, holdsMediaControl = true))
    }

    @Test
    fun `any other app is refused, whatever it calls itself`() {
        assertFalse(ControllerRule.admits("com.example.listener", theApp, holdsMediaControl = false))
        assertFalse(ControllerRule.admits("$theApp.evil", theApp, holdsMediaControl = false))
        assertFalse(ControllerRule.admits("app.lemonfiber", theApp, holdsMediaControl = false))
    }

    @Test
    fun `an asker with no name is refused`() {
        assertFalse(ControllerRule.admits("", "", holdsMediaControl = false))
        assertFalse(ControllerRule.admits("", theApp, holdsMediaControl = true))
    }
}
