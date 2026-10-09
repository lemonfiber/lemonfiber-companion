package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * What the player says when the app asks where it is.
 *
 * The same cases as `PlayerStateTests.swift`, in the same order.
 */
class PlayerStateTest {
    private val english = OfferedTrack("a1", "en", "English", isDefault = true)
    private val dutch = OfferedTrack("s1", "nl", "Nederlands", isDefault = false)

    @Test
    fun `a state is answered in closed words and numbers, with every track`() {
        val said =
            PlayerState(
                WherePlaybackStands.PLAYING,
                61.5,
                5400.0,
                listOf(english),
                listOf(dutch),
                "a1",
                "s1",
                null,
            )
                .answer()

        assertEquals("playing", said["stands"])
        assertEquals(61.5, said["position"])
        assertEquals(5400.0, said["duration"])
        assertEquals(listOf(mapOf("id" to "a1", "language" to "en", "label" to "English")), said["audio"])
        assertEquals(
            listOf(mapOf("id" to "s1", "language" to "nl", "label" to "Nederlands")),
            said["subtitles"],
        )
        assertEquals("a1", said["chosen_audio"])
        assertEquals("s1", said["chosen_subtitle"])
    }

    @Test
    fun `a player that stopped says why, and one that did not says nothing`() {
        val stopped =
            PlayerState(
                WherePlaybackStands.STOPPED,
                12.0,
                5400.0,
                emptyList(),
                emptyList(),
                null,
                null,
                WhyPlaybackStopped.UNREACHABLE,
            )

        assertEquals("unreachable", stopped.answer()["why"])
        assertEquals("", PlayerState.CLOSED.answer()["why"])
    }

    @Test
    fun `nothing chosen is answered as empty`() {
        val said = PlayerState.CLOSED.answer()

        assertEquals("closed", said["stands"])
        assertEquals("", said["chosen_audio"])
        assertEquals("", said["chosen_subtitle"])
    }

    @Test
    fun `each place playback stands is one word on the wire`() {
        assertEquals("opening", WherePlaybackStands.OPENING.word)
        assertEquals("playing", WherePlaybackStands.PLAYING.word)
        assertEquals("paused", WherePlaybackStands.PAUSED.word)
        assertEquals("stalled", WherePlaybackStands.STALLED.word)
        assertEquals("ended", WherePlaybackStands.ENDED.word)
        assertEquals("stopped", WherePlaybackStands.STOPPED.word)
        assertEquals("closed", WherePlaybackStands.CLOSED.word)
    }
}
