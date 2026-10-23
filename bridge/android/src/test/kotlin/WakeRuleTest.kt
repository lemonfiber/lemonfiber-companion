package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * What a wake is known by and runs.
 *
 * The same case as `WakeRuleTests.swift`.
 */
class WakeRuleTest {
    @Test
    fun `a wake runs the command that hears the stacks, under one name`() {
        assertEquals("lemonfiber:hear-while-away", WakeRule.COMMAND)
        assertEquals("app.lemonfiber.wake", WakeRule.NAME)
    }
}
