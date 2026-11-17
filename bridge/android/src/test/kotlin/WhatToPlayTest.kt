package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * What the app asked the player to play, read and checked before anything is fetched.
 *
 * The same cases as `WhatToPlayTests.swift`, in the same order.
 */
class WhatToPlayTest {
    private val theFingerprint = "13ee3a6685a324f7ebbfebb922980ba07a74c0e54cf2243b2f199dc81912e9f5"

    private fun aRequest(changed: Map<String, Any> = emptyMap()): Map<String, Any> =
        mapOf(
            "location" to "https://door.home:8443/library/1/main.m3u8",
            "fingerprint" to theFingerprint,
            "grant" to "0123456789abcdef0123456789abcdef",
            "start_at" to 0,
            "title" to "Arrival",
            "audio" to "en",
            "subtitle" to "off",
        ) + changed

    private fun whyNot(read: WhatToPlay.Read): WhatToPlay.WhyNot? = (read as? WhatToPlay.Read.Refused)?.why

    private fun toPlay(read: WhatToPlay.Read): WhatToPlay? = (read as? WhatToPlay.Read.ToPlay)?.asked

    @Test
    fun `a request that holds is read whole`() {
        val asked = toPlay(WhatToPlay.read(aRequest(mapOf("start_at" to 61.5))))

        assertEquals("https://door.home:8443/library/1/main.m3u8", asked?.location)
        assertEquals("door.home", asked?.door?.host)
        assertEquals("0123456789abcdef0123456789abcdef", asked?.grant)
        assertEquals(61.5, asked?.startAt)
        assertEquals("Arrival", asked?.shown?.title)
        assertEquals("en", asked?.shown?.audio)
        assertEquals("off", asked?.shown?.subtitle)
    }

    @Test
    fun `a starting point sent as a whole number is read as seconds`() {
        assertEquals(90.0, toPlay(WhatToPlay.read(aRequest(mapOf("start_at" to 90))))?.startAt)
    }

    @Test
    fun `a location that is not at a door is refused`() {
        assertEquals(
            WhatToPlay.WhyNot.NOT_AT_A_DOOR,
            whyNot(WhatToPlay.read(aRequest(mapOf("location" to "http://door.home/library/1/main.m3u8")))),
        )
        assertEquals(
            WhatToPlay.WhyNot.NOT_AT_A_DOOR,
            whyNot(WhatToPlay.read(aRequest(mapOf("location" to 7)))),
        )
    }

    @Test
    fun `a fingerprint that is not one is refused`() {
        assertEquals(
            WhatToPlay.WhyNot.UNPINNED,
            whyNot(WhatToPlay.read(aRequest(mapOf("fingerprint" to "abc")))),
        )
        assertEquals(WhatToPlay.WhyNot.UNPINNED, whyNot(WhatToPlay.read(aRequest(mapOf("fingerprint" to 7)))))
    }

    @Test
    fun `a grant that is not 32 lowercase hexadecimal digits is refused`() {
        val notAsIssued =
            listOf<Any>(
                "",
                7,
                "a grant",
                "a-grant\r\nX-Other: 1",
                "a-grant-é",
                "0123456789ABCDEF0123456789ABCDEF",
                "0123456789abcdef0123456789abcde",
                "0123456789abcdef0123456789abcdef0",
                "\"0123456789abcdef0123456789abcde\"",
                "0123456789abcdef%30123456789abcdef",
                "0123456789abcdeg0123456789abcdef",
            )

        for (grant in notAsIssued) {
            assertEquals(
                WhatToPlay.WhyNot.NO_GRANT,
                whyNot(WhatToPlay.read(aRequest(mapOf("grant" to grant)))),
            )
        }
    }

    @Test
    fun `the grant is sent exactly as issued, after Bearer, in the Authorization header`() {
        val asked = toPlay(WhatToPlay.read(aRequest(emptyMap())))

        assertEquals("Authorization", WhatToPlay.GRANT_HEADER)
        assertEquals("Bearer 0123456789abcdef0123456789abcdef", asked?.grantHeaderValue)
    }

    @Test
    fun `a grant written into the address is refused`() {
        val location = "https://door.home:8443/library/1/0123456789abcdef0123456789abcdef/main.m3u8"

        assertEquals(
            WhatToPlay.WhyNot.GRANT_IN_THE_ADDRESS,
            whyNot(WhatToPlay.read(aRequest(mapOf("location" to location)))),
        )
    }

    @Test
    fun `a starting point that is not a time is refused`() {
        assertEquals(
            WhatToPlay.WhyNot.NO_STARTING_POINT,
            whyNot(WhatToPlay.read(aRequest(mapOf("start_at" to -1)))),
        )
        assertEquals(
            WhatToPlay.WhyNot.NO_STARTING_POINT,
            whyNot(WhatToPlay.read(aRequest(mapOf("start_at" to "soon")))),
        )
    }

    @Test
    fun `each refusal is one word on the wire`() {
        assertEquals("not_at_a_door", WhatToPlay.WhyNot.NOT_AT_A_DOOR.word)
        assertEquals("unpinned", WhatToPlay.WhyNot.UNPINNED.word)
        assertEquals("no_grant", WhatToPlay.WhyNot.NO_GRANT.word)
        assertEquals("grant_in_the_address", WhatToPlay.WhyNot.GRANT_IN_THE_ADDRESS.word)
        assertEquals("no_starting_point", WhatToPlay.WhyNot.NO_STARTING_POINT.word)
        assertEquals("nowhere_to_play", WhatToPlay.WhyNot.NOWHERE_TO_PLAY.word)
    }

    private val theDirect = "https://door.home:8920/live/s1/stream.ts?lfpass=p.1.m"

    private val theTranscoded = "https://door.home:8920/live/s1/main.m3u8?lfpass=p.1.m"

    private val theDirectInTheClear = "http://door.home:8920/live/s1/stream.ts?lfpass=p"

    private val theTranscodedElsewhere = "https://elsewhere.home:8920/live/s1/main.m3u8?lfpass=p"

    private val theLocationWithAGrant =
        "https://door.home:8443/library/1/main.m3u8?t=0123456789abcdef0123456789abcdef"

    private fun aChannel(changed: Map<String, Any> = emptyMap()): Map<String, Any> =
        mapOf(
            "direct" to theDirect,
            "transcoded" to theTranscoded,
            "fingerprint" to theFingerprint,
            "title" to "NPO 1",
        ) + changed

    @Test
    fun `a channel plays what the player here decodes best, with no grant beside its pass`() {
        val asked = toPlay(WhatToPlay.readChannel(aChannel()))

        assertEquals(theDirect, asked?.location)
        assertEquals(theTranscoded, asked?.fallback)
        assertEquals(false, asked?.carriesAGrant)
        assertEquals(true, asked?.isLive)
        assertEquals(0.0, asked?.startAt)
        assertEquals("NPO 1", asked?.shown?.title)
    }

    @Test
    fun `a channel never carries a grant, even one sent beside it`() {
        val sent = aChannel(mapOf("grant" to "0123456789abcdef0123456789abcdef"))
        val asked = toPlay(WhatToPlay.readChannel(sent))

        assertEquals("", asked?.grant)
        assertEquals(false, asked?.carriesAGrant)
    }

    @Test
    fun `a channel ends on its transcoded playlist, with nothing past it to fall back to`() {
        val instead = toPlay(WhatToPlay.readChannel(aChannel()))?.fallenBack()

        assertEquals(theTranscoded, instead?.location)
        assertEquals(true, instead?.isLive)
        assertEquals(null, instead?.fallenBack())
    }

    @Test
    fun `a channel served only one way plays that one, with nothing to fall back to`() {
        val transcodedOnly = toPlay(WhatToPlay.readChannel(aChannel(mapOf("direct" to ""))))
        val directOnly = toPlay(WhatToPlay.readChannel(aChannel(mapOf("transcoded" to ""))))

        assertEquals(theTranscoded, transcodedOnly?.location)
        assertEquals(null, transcodedOnly?.fallenBack())
        assertEquals(theDirect, directOnly?.location)
        assertEquals(null, directOnly?.fallenBack())
    }

    @Test
    fun `a channel served nowhere, off a door, at two doors or unpinned is refused`() {
        assertEquals(
            WhatToPlay.WhyNot.NOWHERE_TO_PLAY,
            whyNot(WhatToPlay.readChannel(aChannel(mapOf("direct" to "", "transcoded" to "")))),
        )
        assertEquals(
            WhatToPlay.WhyNot.NOT_AT_A_DOOR,
            whyNot(WhatToPlay.readChannel(aChannel(mapOf("direct" to theDirectInTheClear)))),
        )
        assertEquals(
            WhatToPlay.WhyNot.NOT_AT_A_DOOR,
            whyNot(WhatToPlay.readChannel(aChannel(mapOf("transcoded" to theTranscodedElsewhere)))),
        )
        assertEquals(
            WhatToPlay.WhyNot.UNPINNED,
            whyNot(WhatToPlay.readChannel(aChannel(mapOf("fingerprint" to "abc")))),
        )
    }

    @Test
    fun `a channel reads a field it was not sent as empty`() {
        assertEquals(WhatToPlay.WhyNot.NOWHERE_TO_PLAY, whyNot(WhatToPlay.readChannel(emptyMap())))
        assertEquals(WhatToPlay.WhyNot.UNPINNED, whyNot(WhatToPlay.readChannel(mapOf("direct" to theDirect))))
        val sent = mapOf("transcoded" to theTranscoded, "fingerprint" to theFingerprint)
        val untitled = toPlay(WhatToPlay.readChannel(sent))

        assertEquals("", untitled?.shown?.title)
    }

    @Test
    fun `a title is still refused with a grant in its address, which only a channel's pass may be`() {
        assertEquals(
            WhatToPlay.WhyNot.GRANT_IN_THE_ADDRESS,
            whyNot(WhatToPlay.read(aRequest(mapOf("location" to theLocationWithAGrant)))),
        )
    }
}
