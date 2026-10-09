package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

/**
 * Which sound and which subtitles to start with.
 *
 * The same cases as `TrackRuleTests.swift`, in the same order.
 */
class TrackRuleTest {
    private val english = OfferedTrack("a1", "en-GB", "English", isDefault = false)
    private val dutch = OfferedTrack("a2", "nl", "Nederlands", isDefault = true)
    private val unsaid = OfferedTrack("a3", "", "Track 3", isDefault = false)

    @Test
    fun `the member's language is played, matched without its region`() {
        assertEquals("a1", TrackRule.audio(listOf(dutch, english), "EN"))
        assertEquals("a1", TrackRule.audio(listOf(dutch, english), "en_US"))
    }

    @Test
    fun `without a match the stream's default plays, and without one the first`() {
        assertEquals("a2", TrackRule.audio(listOf(english, dutch), "fr"))
        assertEquals("a3", TrackRule.audio(listOf(unsaid, english), ""))
        assertNull(TrackRule.audio(emptyList(), "en"))
    }

    @Test
    fun `subtitles in the member's language are shown`() {
        assertEquals("a2", TrackRule.subtitle(listOf(dutch, english), "nl-BE"))
    }

    @Test
    fun `subtitles are never forced on`() {
        // No preference, an explicit off, and a language the stream does not
        // carry all answer none, never the stream's default.
        assertNull(TrackRule.subtitle(listOf(dutch, english), ""))
        assertNull(TrackRule.subtitle(listOf(dutch, english), TrackRule.OFF))
        assertNull(TrackRule.subtitle(listOf(dutch, english), "fr"))
    }
}
