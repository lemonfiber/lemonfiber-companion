package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * Which addresses a name resolved to the app may send to.
 *
 * The same six cases as `ResolveRuleTests.swift`, in the same order.
 */
class ResolveRuleTest {
    @Test
    fun `a look-up that found nothing says so, with no address`() {
        assertEquals(WhatTheLookupFound.NOTHING, ResolveRule.NOTHING_FOUND.said)
        assertEquals(emptyList<String>(), ResolveRule.NOTHING_FOUND.usable)
    }

    @Test
    fun `an IPv4 address is found and usable`() {
        val found =
            ResolveRule(listOf(AnAddressFound("192.168.1.42", isVersion4 = true, needsAnInterface = false)))

        assertEquals(WhatTheLookupFound.FOUND, found.said)
        assertEquals(listOf("192.168.1.42"), found.usable)
    }

    @Test
    fun `IPv4 comes before IPv6, each in the order the platform gave`() {
        val found =
            ResolveRule(
                listOf(
                    AnAddressFound("2001:db8::2", isVersion4 = false, needsAnInterface = false),
                    AnAddressFound("192.168.1.42", isVersion4 = true, needsAnInterface = false),
                    AnAddressFound("2001:db8::1", isVersion4 = false, needsAnInterface = false),
                    AnAddressFound("10.0.0.7", isVersion4 = true, needsAnInterface = false),
                ),
            )

        assertEquals(listOf("192.168.1.42", "10.0.0.7", "2001:db8::2", "2001:db8::1"), found.usable)
    }

    @Test
    fun `an address that needs its interface is dropped`() {
        val found =
            ResolveRule(
                listOf(
                    AnAddressFound("fe80::1%wlan0", isVersion4 = false, needsAnInterface = true),
                    AnAddressFound("192.168.1.42", isVersion4 = true, needsAnInterface = false),
                ),
            )

        assertEquals(listOf("192.168.1.42"), found.usable)
    }

    @Test
    fun `only unusable addresses are nothing found`() {
        val found =
            ResolveRule(
                listOf(
                    AnAddressFound("fe80::1%wlan0", isVersion4 = false, needsAnInterface = true),
                    AnAddressFound("", isVersion4 = true, needsAnInterface = false),
                ),
            )

        assertEquals(WhatTheLookupFound.NOTHING, found.said)
        assertEquals(emptyList<String>(), found.usable)
    }

    @Test
    fun `an address given twice is sent to once, and each answer is one word on the wire`() {
        val found =
            ResolveRule(
                listOf(
                    AnAddressFound("192.168.1.42", isVersion4 = true, needsAnInterface = false),
                    AnAddressFound("192.168.1.42", isVersion4 = true, needsAnInterface = false),
                ),
            )

        assertEquals(listOf("192.168.1.42"), found.usable)
        assertEquals("found", WhatTheLookupFound.FOUND.word)
        assertEquals("nothing", WhatTheLookupFound.NOTHING.word)
    }
}
