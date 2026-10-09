package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertTrue

/**
 * When the player reports how far it got, and when it stops waiting.
 *
 * The same cases as `PlaybackRuleTests.swift`, in the same order.
 */
class PlaybackRuleTest {
    @Test
    fun `progress is reported every ten seconds while playing`() {
        assertFalse(PlaybackRule.reportsProgress(9.9, isPlaying = true, moved = false))
        assertTrue(PlaybackRule.reportsProgress(10.0, isPlaying = true, moved = false))
    }

    @Test
    fun `nothing is reported while nothing moves`() {
        assertFalse(PlaybackRule.reportsProgress(60.0, isPlaying = false, moved = false))
    }

    @Test
    fun `a pause, a seek, the end or a close is reported at once`() {
        assertTrue(PlaybackRule.reportsProgress(0.0, isPlaying = false, moved = true))
    }

    @Test
    fun `a stall is waited out for ten seconds and no longer`() {
        assertFalse(PlaybackRule.givesUp(9.9))
        assertTrue(PlaybackRule.givesUp(10.0))
    }

    @Test
    fun `a door's refusal, an unservable format and anything else are told apart`() {
        for (refused in listOf(401, 403, 404, 410)) {
            assertEquals(WhyPlaybackStopped.REFUSED, PlaybackRule.why(refused))
        }

        assertEquals(WhyPlaybackStopped.UNSUPPORTED_FORMAT, PlaybackRule.why(415))
        assertEquals(WhyPlaybackStopped.UNREACHABLE, PlaybackRule.why(502))
        assertEquals(WhyPlaybackStopped.UNREACHABLE, PlaybackRule.why(0))
    }

    @Test
    fun `each reason is one word on the wire`() {
        assertEquals("unreachable", WhyPlaybackStopped.UNREACHABLE.word)
        assertEquals("pin_mismatch", WhyPlaybackStopped.PIN_MISMATCH.word)
        assertEquals("unsupported_format", WhyPlaybackStopped.UNSUPPORTED_FORMAT.word)
        assertEquals("refused", WhyPlaybackStopped.REFUSED.word)
    }
}
