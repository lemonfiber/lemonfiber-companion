package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * Byte ranges, as the player asks for part of a file and checks what came back.
 *
 * The same cases as `RangeRuleTests.swift`, in the same order.
 */
class RangeRuleTest {
    @Test
    fun `reading from the start to the end asks for no range`() {
        assertNull(RangeRule.asked(0, null))
    }

    @Test
    fun `reading from a byte asks for everything after it`() {
        assertEquals("bytes=1024-", RangeRule.asked(1024, null))
    }

    @Test
    fun `reading a length asks for exactly those bytes`() {
        assertEquals("bytes=0-1", RangeRule.asked(0, 2))
        assertEquals("bytes=100-149", RangeRule.asked(100, 50))
    }

    @Test
    fun `the whole file's length is read off the content range where it says`() {
        assertEquals(1000L, RangeRule.total("bytes 0-99/1000"))
        assertEquals(1000L, RangeRule.total("bytes */1000"))
        assertNull(RangeRule.total("bytes 0-99/*"))
        assertNull(RangeRule.total("bytes 0-99"))
        assertNull(RangeRule.total(null))
    }

    @Test
    fun `only the answer that was asked for is admitted`() {
        // A range answered with the whole file would hand the player the wrong
        // bytes at the right position.
        assertTrue(RangeRule.admits(206, true))
        assertFalse(RangeRule.admits(200, true))
        assertTrue(RangeRule.admits(200, false))
        assertFalse(RangeRule.admits(206, false))
        assertFalse(RangeRule.admits(500, false))
    }
}
