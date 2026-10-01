package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * What asking for the app's settings page came to.
 *
 * The same three cases as `SettingsRuleTests.swift`, in the same order.
 */
class SettingsRuleTest {
    @Test
    fun `a page the platform took the request for is opened`() {
        assertEquals(WhetherTheSettingsOpened.OPENED, SettingsRule(pageWasAskedFor = true).said)
    }

    @Test
    fun `a page the platform would not open is refused`() {
        assertEquals(WhetherTheSettingsOpened.REFUSED, SettingsRule(pageWasAskedFor = false).said)
    }

    @Test
    fun `the words on the wire are the ones the app reads`() {
        assertEquals("opened", WhetherTheSettingsOpened.OPENED.word)
        assertEquals("refused", WhetherTheSettingsOpened.REFUSED.word)
    }
}
