package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertNull

/**
 * Every extension the player was built with, and what they decide together.
 *
 * The same cases as `PlayerExtensionsTests.swift`, in the same order.
 */
class PlayerExtensionsTest {
    private val theFingerprint = "13ee3a6685a324f7ebbfebb922980ba07a74c0e54cf2243b2f199dc81912e9f5"

    private fun asked(location: String = "https://door.home:8443/library/1/main.m3u8"): WhatToPlay {
        val read =
            WhatToPlay.read(
                mapOf(
                    "location" to location,
                    "fingerprint" to theFingerprint,
                    "grant" to "a-grant",
                    "start_at" to 0,
                ),
            )

        return assertNotNull((read as? WhatToPlay.Read.ToPlay)?.asked)
    }

    private class AResolver(
        private val claiming: Boolean,
        private val address: String,
    ) : SourceResolver {
        override fun claims(asked: WhatToPlay): Boolean = claiming

        override fun source(asked: WhatToPlay): PlayableSource =
            PlayableSource(
                address,
                KindOfSource.PROGRESSIVE,
            )
    }

    @Test
    fun `a request no extension claims is played from where the core said`() {
        val extensions = PlayerExtensions()
        extensions.registerSource(AResolver(false, "https://door.home:8443/other.mp4"))

        assertEquals(
            PlayableSource("https://door.home:8443/library/1/main.m3u8", KindOfSource.HLS),
            extensions.source(asked()),
        )
    }

    @Test
    fun `the first extension to claim a request resolves it`() {
        val extensions = PlayerExtensions()
        extensions.registerSource(AResolver(true, "https://door.home:8443/first.mp4"))
        extensions.registerSource(AResolver(true, "https://door.home:8443/second.mp4"))

        assertEquals("https://door.home:8443/first.mp4", extensions.source(asked())?.address)
    }

    @Test
    fun `an extension that resolves off the door resolves nothing`() {
        val extensions = PlayerExtensions()
        extensions.registerSource(AResolver(true, "https://elsewhere.example/a.mp4"))

        assertNull(extensions.source(asked()))
    }

    @Test
    fun `extra tracks off the door are not offered`() {
        val extensions = PlayerExtensions()
        extensions.registerTracks {
            listOf(
                ExtraTrack(
                    "s1",
                    KindOfTrack.SUBTITLE,
                    "en",
                    "English",
                    "https://door.home:8443/library/1/en.vtt",
                ),
                ExtraTrack(
                    "s2",
                    KindOfTrack.SUBTITLE,
                    "nl",
                    "Nederlands",
                    "https://elsewhere.example/nl.vtt",
                ),
            )
        }

        assertEquals(listOf("s1"), extensions.extraTracks(asked()).map { it.id })
    }

    @Test
    fun `every observer is told, in the order it was added`() {
        val heard = mutableListOf<String>()
        val extensions = PlayerExtensions()
        extensions.registerObserver { heard.add("first: $it") }
        extensions.registerObserver { heard.add("second: $it") }

        extensions.tell(PlayerHappening.Ended)

        assertEquals(listOf("first: Ended", "second: Ended"), heard)
    }

    @Test
    fun `every happening reaches an observer with what it carries`() {
        val heard = mutableListOf<PlayerHappening>()
        val extensions = PlayerExtensions()
        extensions.registerObserver { heard.add(it) }
        val told =
            listOf(
                PlayerHappening.Ready(5400.0),
                PlayerHappening.Progressed(10.0),
                PlayerHappening.Paused(12.0),
                PlayerHappening.Ended,
                PlayerHappening.Stopped(WhyPlaybackStopped.PIN_MISMATCH),
                PlayerHappening.Closed(12.0),
            )

        told.forEach { extensions.tell(it) }

        assertEquals(told, heard)
    }

    @Test
    fun `every route on offer is listed`() {
        val extensions = PlayerExtensions()
        extensions.registerRoutes { listOf(PlaybackRoute("living-room", "Living room")) }

        assertEquals(listOf(PlaybackRoute("living-room", "Living room")), extensions.routes())
    }

    @Test
    fun `how a stream is packaged is read from the end of its path`() {
        assertEquals(KindOfSource.HLS, PlayerExtensions.kind("https://door.home/a/main.M3U8?x=1"))
        assertEquals(KindOfSource.DASH, PlayerExtensions.kind("https://door.home/a/manifest.mpd#t=1"))
        assertEquals(KindOfSource.PROGRESSIVE, PlayerExtensions.kind("https://door.home/a/film.mkv"))
    }
}
