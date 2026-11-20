package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * Which pages the app opens, and what asking for one came to.
 *
 * The same cases as `PageRuleTests.swift`, in the same order.
 */
class PageRuleTest {
    @Test
    fun `an https page the platform took is opened`() {
        val page = PageRule(address = "https://approve.example/link")

        assertEquals(WhetherThePageOpened.OPENED, page.said(pageWasAskedFor = true))
    }

    @Test
    fun `an https page the platform would not take is refused`() {
        val page = PageRule(address = "https://approve.example/link")

        assertEquals(WhetherThePageOpened.REFUSED, page.said(pageWasAskedFor = false))
    }

    @Test
    fun `only an https address naming somewhere may be opened`() {
        assertTrue(PageRule(address = "HTTPS://approve.example").mayOpen)
        assertFalse(PageRule(address = "http://approve.example").mayOpen)
        assertFalse(PageRule(address = "intent://approve.example").mayOpen)
        assertFalse(PageRule(address = "https://").mayOpen)
        assertFalse(PageRule(address = "https://approve.example/a b").mayOpen)
        assertFalse(PageRule(address = "https://approve.example/\n").mayOpen)
        assertFalse(PageRule(address = "").mayOpen)
    }

    @Test
    fun `an address that may not be opened is refused even where the platform took it`() {
        val page = PageRule(address = "http://approve.example")

        assertEquals(WhetherThePageOpened.REFUSED, page.said(pageWasAskedFor = true))
    }

    @Test
    fun `the words are the wire's`() {
        assertEquals("opened", WhetherThePageOpened.OPENED.word)
        assertEquals("refused", WhetherThePageOpened.REFUSED.word)
    }
}
