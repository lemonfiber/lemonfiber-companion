package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

/**
 * An HLS playlist, read so that every address in it stays at the door.
 *
 * The same cases as `PlaylistRuleTests.swift`, in the same order.
 */
class PlaylistRuleTest {
    private val theAddress = "https://door.home:8443/library/1/main.m3u8"

    private fun aRule(scheme: String = "https"): PlaylistRule? =
        Door.of(theAddress)?.let {
            PlaylistRule(it, scheme)
        }

    @Test
    fun `a media playlist's segments are made absolute at the door`() {
        val playlist = "#EXTM3U\n#EXTINF:6.0,\nseg-1.ts\n#EXTINF:6.0,\n/library/1/seg-2.ts\n"

        assertEquals(
            "#EXTM3U\n#EXTINF:6.0,\nhttps://door.home:8443/library/1/seg-1.ts\n" +
                "#EXTINF:6.0,\nhttps://door.home:8443/library/1/seg-2.ts\n",
            aRule()?.rewrite(playlist, theAddress),
        )
    }

    @Test
    fun `every address a tag names is rewritten, from the key and the map to a rendition`() {
        val playlist =
            "#EXT-X-KEY:METHOD=AES-128,URI=\"key.bin\",IV=0x1\n" +
                "#EXT-X-MAP:URI=\"init.mp4\"\n" +
                "#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID=\"subs\",LANGUAGE=\"en\",URI=\"subs/en.m3u8\""

        assertEquals(
            "#EXT-X-KEY:METHOD=AES-128,URI=\"https://door.home:8443/library/1/key.bin\",IV=0x1\n" +
                "#EXT-X-MAP:URI=\"https://door.home:8443/library/1/init.mp4\"\n" +
                "#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID=\"subs\",LANGUAGE=\"en\"," +
                "URI=\"https://door.home:8443/library/1/subs/en.m3u8\"",
            aRule()?.rewrite(playlist, theAddress),
        )
    }

    @Test
    fun `a playlist naming anything off the door is refused whole`() {
        assertNull(aRule()?.rewrite("#EXTINF:6.0,\nseg-1.ts\nhttps://elsewhere.example/seg-2.ts", theAddress))
        assertNull(aRule()?.rewrite("#EXT-X-KEY:METHOD=SAMPLE-AES,URI=\"skd://key\"", theAddress))
        assertNull(aRule()?.rewrite("#EXT-X-MAP:URI=\"init.mp4", theAddress))
    }

    @Test
    fun `addresses are handed over under the scheme the player reads them by`() {
        assertEquals(
            "lfdoor://door.home:8443/library/1/seg-1.ts\n" +
                "#EXT-X-MAP:URI=\"lfdoor://door.home:8443/library/1/init.mp4\"",
            aRule("lfdoor")?.rewrite("seg-1.ts\n#EXT-X-MAP:URI=\"init.mp4\"", theAddress),
        )
    }

    @Test
    fun `tags without addresses and blank lines pass through as they were`() {
        val playlist = "#EXTM3U\n\n#EXT-X-VERSION:7\n#EXT-X-TARGETDURATION:6"

        assertEquals(playlist, aRule()?.rewrite(playlist, theAddress))
    }

    @Test
    fun `lines ending in a carriage return are read as lines`() {
        assertEquals(
            "#EXTM3U\nhttps://door.home:8443/library/1/seg-1.ts\n",
            aRule()?.rewrite("#EXTM3U\r\nseg-1.ts\r\n", theAddress),
        )
    }
}
