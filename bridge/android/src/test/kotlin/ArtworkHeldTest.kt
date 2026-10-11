package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertContentEquals
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * Artwork held in memory: what is held, for which stack, and what lets go of it.
 *
 * The same cases as `ArtworkHeldTests.swift`, in the same order.
 */
class ArtworkHeldTest {
    private val aKey = "lemonfiber-art:0123456789abcdef0123456789abcdef"

    private val anotherKey = "lemonfiber-art:fedcba9876543210fedcba9876543210"

    private fun bytes(count: Int): ByteArray = ByteArray(count) { 7 }

    @Test
    fun `a picture is held under its key, byte for byte`() {
        val store = ArtworkHeld(1024)

        assertTrue(store.hold(aKey, "attic", bytes(10)))
        assertContentEquals(bytes(10), store.bytes(aKey))
        assertTrue(store.holds(aKey))
        assertFalse(store.holds(anotherKey))
    }

    @Test
    fun `a key that is not one, a stack named by nothing, no bytes and too many bytes are refused`() {
        val store = ArtworkHeld(ArtworkHeld.MOST_BYTES)

        assertFalse(store.hold("https://example.org/poster.jpg", "attic", bytes(10)))
        assertFalse(store.hold("lemonfiber-art:SHOUTING0123456789", "attic", bytes(10)))
        assertFalse(store.hold("lemonfiber-art:abc", "attic", bytes(10)))
        assertFalse(store.hold(aKey, "", bytes(10)))
        assertFalse(store.hold(aKey, "attic", ByteArray(0)))
        assertFalse(store.hold(aKey, "attic", bytes(ArtworkHeld.MOST_BYTES_OF_ONE + 1)))
        assertFalse(store.holds(aKey))
    }

    @Test
    fun `letting go of a stack lets go of its pictures and no other's`() {
        val store = ArtworkHeld(1024)
        store.hold(aKey, "attic", bytes(10))
        store.hold(anotherKey, "loft", bytes(10))

        assertEquals(listOf(aKey), store.letGoOf("attic"))
        assertFalse(store.holds(aKey))
        assertTrue(store.holds(anotherKey))
    }

    @Test
    fun `letting go of every picture leaves nothing held`() {
        val store = ArtworkHeld(1024)
        store.hold(aKey, "attic", bytes(10))
        store.hold(anotherKey, "loft", bytes(10))

        assertEquals(setOf(aKey, anotherKey), store.letGoOfEvery().toSet())
        assertFalse(store.holds(aKey))
        assertFalse(store.holds(anotherKey))
    }

    @Test
    fun `past the bound the oldest picture is let go of first, and a key held again counts once`() {
        val store = ArtworkHeld(25)
        store.hold(aKey, "attic", bytes(10))
        store.hold(aKey, "attic", bytes(10))
        store.hold(anotherKey, "attic", bytes(10))

        assertTrue(store.holds(aKey))

        store.hold("lemonfiber-art:00000000000000000000000000000000", "attic", bytes(10))

        assertFalse(store.holds(aKey))
        assertTrue(store.holds(anotherKey))
    }

    @Test
    fun `the app draws from one store, bounded by the most bytes held at once`() {
        ArtworkHeld.shared.letGoOfEvery()

        assertTrue(ArtworkHeld.shared.hold(aKey, "attic", bytes(10)))
        assertFalse(ArtworkHeld.shared.hold(anotherKey, "attic", bytes(ArtworkHeld.MOST_BYTES_OF_ONE + 1)))
        assertEquals(listOf(aKey), ArtworkHeld.shared.letGoOfEvery())
    }
}
