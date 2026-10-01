package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * The distinction the local-network probe turns on.
 *
 * A refused permission and a machine that is off are the same silence at the
 * socket. The platform says which, and only the refusal is read.
 *
 * The same five cases as `LocalNetworkRuleTests.swift`, in the same order.
 */
class LocalNetworkRuleTest {
    @Test
    fun `a path the platform let be taken is open`() {
        assertEquals(WhetherTheLocalNetworkIsOpen.OPEN, LocalNetworkRule.PERMITTED.said)
    }

    @Test
    fun `a path the platform denied for the permission is forbidden`() {
        val denied = LocalNetworkRule(pathWasReadable = true, platformDeniedIt = true)

        assertEquals(WhetherTheLocalNetworkIsOpen.FORBIDDEN, denied.said)
    }

    @Test
    fun `a platform that said nothing is read as open`() {
        // Every machine that is not a handset, and every Android phone: the app
        // then reports the stack as not answering, which is what it always did.
        assertEquals(WhetherTheLocalNetworkIsOpen.OPEN, LocalNetworkRule.UNASKED.said)
    }

    @Test
    fun `having said nothing wins over a denial nobody heard`() {
        val unread = LocalNetworkRule(pathWasReadable = false, platformDeniedIt = true)

        assertEquals(WhetherTheLocalNetworkIsOpen.OPEN, unread.said)
    }

    @Test
    fun `each answer is one word on the wire`() {
        assertEquals("open", WhetherTheLocalNetworkIsOpen.OPEN.word)
        assertEquals("forbidden", WhetherTheLocalNetworkIsOpen.FORBIDDEN.word)
    }
}
