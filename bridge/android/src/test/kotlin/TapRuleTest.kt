package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

/**
 * Where a tap on a notification may lead.
 *
 * The same cases as `TapRuleTests.swift`, in the same order.
 */
class TapRuleTest {
    @Test
    fun `a link into the app is kept`() {
        assertEquals("lemonfiber://stacks/abc123", TapRule.opens("lemonfiber://stacks/abc123"))
    }

    @Test
    fun `a web page is not opened`() {
        assertNull(TapRule.opens("https://example.org/stacks/abc123"))
    }

    @Test
    fun `another app's scheme is not opened`() {
        assertNull(TapRule.opens("otherapp://stacks/abc123"))
    }

    @Test
    fun `a link naming no screen is not opened`() {
        assertNull(TapRule.opens("lemonfiber://"))
    }

    @Test
    fun `a link carrying credentials or a port is not opened`() {
        assertNull(TapRule.opens("lemonfiber://someone:secret@stacks/abc123"))
        assertNull(TapRule.opens("lemonfiber://stacks:8080/abc123"))
    }

    @Test
    fun `nothing leads nowhere`() {
        assertNull(TapRule.opens(null))
        assertNull(TapRule.opens(""))
        assertNull(TapRule.opens("not a link"))
    }
}
