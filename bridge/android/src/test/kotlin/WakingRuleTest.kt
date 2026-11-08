package app.lemonfiber.native

import java.math.BigInteger
import kotlin.test.Test
import kotlin.test.assertContentEquals
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/** What waking through a distributor answers with, and what a push for a stack runs. */
class WakingRuleTest {
    private val stack = "0123456789abcdef0123456789abcdef"

    @Test
    fun `a waking key is kept from the release whose keystore agrees keys`() {
        assertFalse(WakingRule.canWakeOn(30))
        assertTrue(WakingRule.canWakeOn(31))
        assertTrue(WakingRule.canWakeOn(35))
    }

    @Test
    fun `a stack's id is an instance, and nothing a command line would read otherwise is`() {
        assertTrue(WakingRule.isAStack(stack))
        assertTrue(WakingRule.isAStack("a_stand-in"))
        assertFalse(WakingRule.isAStack(""))
        assertFalse(WakingRule.isAStack("$stack --env=x"))
        assertFalse(WakingRule.isAStack("a;b"))
        assertFalse(WakingRule.isAStack("a".repeat(129)))
    }

    @Test
    fun `a push runs the wake's command for its stack alone, and runs nothing for what names no stack`() {
        assertEquals("lemonfiber:hear-while-away --stack=$stack", WakingRule.commandFor(stack))
        assertNull(WakingRule.commandFor("a b"))
    }

    @Test
    fun `each stack has its own key pair and its own queue of pushes`() {
        assertEquals("lemonfiber.waking.$stack", WakingRule.aliasFor(stack))
        assertEquals("app.lemonfiber.wake.$stack", WakingRule.workFor(stack))
    }

    @Test
    fun `only a changed endpoint nobody waits for wakes the app`() {
        assertTrue(WakingRule.wakesForANewEndpoint("https://a", "https://b", awaited = false))
        assertFalse(WakingRule.wakesForANewEndpoint("https://a", "https://b", awaited = true))
        assertFalse(WakingRule.wakesForANewEndpoint("https://a", "https://a", awaited = false))
        assertFalse(WakingRule.wakesForANewEndpoint(null, "https://b", awaited = false))
    }

    @Test
    fun `a point is written uncompressed, each coordinate in thirty-two bytes`() {
        val point = WakingRule.uncompressed(BigInteger.ONE, BigInteger.ONE.shiftLeft(255))

        assertEquals(65, point.size)
        assertEquals(0x04.toByte(), point[0])
        assertEquals(1.toByte(), point[32])
        assertContentEquals(ByteArray(31), point.copyOfRange(1, 32))
        assertEquals(0x80.toByte(), point[33])
        assertContentEquals(ByteArray(31), point.copyOfRange(34, 65))
    }
}
