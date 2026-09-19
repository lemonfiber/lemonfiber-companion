package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * The distinction whether there is a link turns on.
 *
 * Not whether a request succeeded — whether *this phone has no network* is told
 * apart from *that machine is not answering*. They are two sentences with two
 * different remedies, and a socket timing out looks the same in both.
 *
 * The same six cases as `LinkRuleTests.swift`, in the same order.
 */
class LinkRuleTest {
    @Test
    fun `a link that works is something the app may try`() {
        assertEquals(WhetherAnythingIsReachable.REACHABLE, LinkRule.CONNECTED.said)
    }

    @Test
    fun `a device with no active link reaches nothing`() {
        val adrift = LinkRule(linkWasReadable = true, hasAnActiveLink = false, carriesTraffic = false)

        assertEquals(WhetherAnythingIsReachable.UNREACHABLE, adrift.said)
    }

    @Test
    fun `a link that carries no traffic reaches nothing`() {
        // Joined to something that is not a way out: the state a phone is in
        // while it is associating, and the one a restricted interface stays in.
        val joined = LinkRule(linkWasReadable = true, hasAnActiveLink = true, carriesTraffic = false)

        assertEquals(WhetherAnythingIsReachable.UNREACHABLE, joined.said)
    }

    @Test
    fun `a platform that cannot be asked is read as a link that works`() {
        // Every machine that is not a handset, and the direction that matters:
        // the app tries and reports what it finds, rather than opening on a
        // screen whose remedy is *turn your wifi on*.
        assertEquals(WhetherAnythingIsReachable.REACHABLE, LinkRule.UNREADABLE.said)
    }

    @Test
    fun `being unable to ask wins over everything else the platform said`() {
        // The combination that proves the order. Both link facts say nothing is
        // reachable, and they are not read at all, because a platform that could
        // not be asked did not supply them.
        val unasked = LinkRule(linkWasReadable = false, hasAnActiveLink = false, carriesTraffic = false)

        assertEquals(WhetherAnythingIsReachable.REACHABLE, unasked.said)
    }

    @Test
    fun `each answer is one word on the wire`() {
        assertEquals("reachable", WhetherAnythingIsReachable.REACHABLE.word)
        assertEquals("unreachable", WhetherAnythingIsReachable.UNREACHABLE.word)
    }
}
