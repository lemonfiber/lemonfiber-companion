package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/** The same cases as `ReorderingTests.swift`, in the same order. */
class ReorderingTest {
    private val three: List<String> = listOf("loft", "attic", "shed")

    @Test
    fun `moves a row down the list`() {
        assertEquals(listOf("attic", "shed", "loft"), Reordering.moved(three, 0, 2))
    }

    @Test
    fun `moves a row up the list`() {
        assertEquals(listOf("shed", "loft", "attic"), Reordering.moved(three, 2, 0))
    }

    @Test
    fun `moves nothing to its own place or outside the list`() {
        assertEquals(three, Reordering.moved(three, 1, 1))
        assertEquals(three, Reordering.moved(three, -1, 1))
        assertEquals(three, Reordering.moved(three, 1, 3))
    }

    @Test
    fun `lands on the nearest place`() {
        assertEquals(1, Reordering.landing(0, 60f, 100f, 3))
        assertEquals(0, Reordering.landing(0, 40f, 100f, 3))
        assertEquals(0, Reordering.landing(2, -180f, 100f, 3))
    }

    @Test
    fun `lands never past either end`() {
        assertEquals(2, Reordering.landing(1, 900f, 100f, 3))
        assertEquals(0, Reordering.landing(1, -900f, 100f, 3))
    }

    @Test
    fun `lands where it was with no rows to measure by`() {
        assertEquals(1, Reordering.landing(1, 60f, 0f, 3))
        assertEquals(1, Reordering.landing(1, 60f, 100f, 0))
    }

    @Test
    fun `sends the keys on a line each`() {
        assertEquals("loft\nattic\nshed", Reordering.sent(three))
    }
}
