package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
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

    @Test
    fun `a quoted value holding a comma is one value of one attribute`() {
        val playlist =
            "#EXT-X-STREAM-INF:BANDWIDTH=1,CODECS=\"avc1.64001f,mp4a.40.2\"\nv.m3u8\n" +
                "#EXT-X-MEDIA:TYPE=AUDIO,NAME=\"English, described\",URI=\"a/en.m3u8\""

        assertEquals(
            "#EXT-X-STREAM-INF:BANDWIDTH=1,CODECS=\"avc1.64001f,mp4a.40.2\"\n" +
                "https://door.home:8443/library/1/v.m3u8\n" +
                "#EXT-X-MEDIA:TYPE=AUDIO,NAME=\"English, described\"," +
                "URI=\"https://door.home:8443/library/1/a/en.m3u8\"",
            aRule()?.rewrite(playlist, theAddress),
        )
    }

    @Test
    fun `every attribute that names an address is rewritten, interstitials and steering among them`() {
        val playlist =
            listOf(
                "#EXT-X-I-FRAME-STREAM-INF:BANDWIDTH=1,URI=\"i.m3u8\"",
                "#EXT-X-SESSION-KEY:METHOD=AES-128,URI=\"k.bin\"",
                "#EXT-X-PRELOAD-HINT:TYPE=PART,URI=\"p.mp4\"",
                "#EXT-X-CONTENT-STEERING:SERVER-URI=\"steer.json\"",
                "#EXT-X-DATERANGE:ID=\"ad\",X-ASSET-URI=\"ad.m3u8\",X-ASSET-LIST=\"ads.json\"",
            ).joinToString("\n")
        val at = "https://door.home:8443/library/1"

        assertEquals(
            listOf(
                "#EXT-X-I-FRAME-STREAM-INF:BANDWIDTH=1,URI=\"$at/i.m3u8\"",
                "#EXT-X-SESSION-KEY:METHOD=AES-128,URI=\"$at/k.bin\"",
                "#EXT-X-PRELOAD-HINT:TYPE=PART,URI=\"$at/p.mp4\"",
                "#EXT-X-CONTENT-STEERING:SERVER-URI=\"$at/steer.json\"",
                "#EXT-X-DATERANGE:ID=\"ad\",X-ASSET-URI=\"$at/ad.m3u8\",X-ASSET-LIST=\"$at/ads.json\"",
            ).joinToString("\n"),
            aRule()?.rewrite(playlist, theAddress),
        )
    }

    @Test
    fun `an absolute address left in any other value refuses the playlist`() {
        val data = "#EXT-X-SESSION-DATA:DATA-ID=\"x\",VALUE="

        assertNull(aRule()?.rewrite("$data\"https://elsewhere.example/a\"", theAddress))
        assertNull(aRule()?.rewrite("${data}https:elsewhere.example", theAddress))
        assertNull(aRule()?.rewrite("$data\"//elsewhere.example/a\"", theAddress))
        assertNull(aRule()?.rewrite("$data\"\\\\elsewhere\\a\"", theAddress))
        assertNull(aRule()?.rewrite("#EXTINF:6.0,https://elsewhere.example/a", theAddress))
        assertNull(aRule()?.rewrite("#EXT-X-MAP:URI=https://door.home:8443/library/1/init.mp4", theAddress))
        assertNull(aRule()?.rewrite("#EXT-X-MAP:URI=\"\"", theAddress))
        assertNull(aRule()?.rewrite("#EXT-X-MAP:URI=init.mp4", theAddress))
        assertNull(aRule()?.rewrite("$data\"see https://elsewhere.example\"", theAddress))
        assertNull(aRule()?.rewrite("$data\"unterminated", theAddress))
        assertNull(aRule()?.rewrite("#EXT-X-MAP:URI=\"", theAddress))
        assertNotNull(aRule()?.rewrite("$data\"seg-1.ts\"", theAddress))
    }

    @Test
    fun `a value that only looks like an address attribute is read by the attribute it belongs to`() {
        assertNull(
            aRule()?.rewrite(
                "#EXT-X-MEDIA:NAME=\"URI=\",URI=\"https://elsewhere.example/a.m3u8\"",
                theAddress,
            ),
        )
        assertEquals(
            "#EXT-X-MEDIA:NAME=\"URI=\",URI=\"https://door.home:8443/library/1/a.m3u8\"",
            aRule()?.rewrite("#EXT-X-MEDIA:NAME=\"URI=\",URI=\"a.m3u8\"", theAddress),
        )
        assertNotNull(aRule()?.rewrite("#EXT-X-MEDIA:\"URI=a.m3u8\",URI=\"a.m3u8\"", theAddress))
        assertEquals(
            "#EXT-X-MEDIA:NAME=\"x,URI=seg.ts\",URI=\"https://door.home:8443/library/1/a.m3u8\"",
            aRule()?.rewrite("#EXT-X-MEDIA:NAME=\"x,URI=seg.ts\",URI=\"a.m3u8\"", theAddress),
        )
    }

    @Test
    fun `a playlist that defines or uses variables is refused`() {
        assertNull(aRule()?.rewrite("#EXT-X-DEFINE:NAME=\"base\",VALUE=\"seg\"", theAddress))
        assertNull(aRule()?.rewrite("#ext-x-define:NAME=\"base\"", theAddress))
        assertNull(aRule()?.rewrite("#EXTINF:6.0,\n{\$base}-1.ts", theAddress))
        assertNull(aRule()?.rewrite("#EXT-X-SESSION-DATA:DATA-ID=\"{\$name}\"", theAddress))
    }

    @Test
    fun `a tag or attribute written in lower case is read all the same`() {
        assertNull(aRule()?.rewrite("#ext-x-map:uri=\"https://elsewhere.example/init.mp4\"", theAddress))
        assertEquals(
            "#ext-x-map:uri=\"https://door.home:8443/library/1/init.mp4\"",
            aRule()?.rewrite("#ext-x-map:uri=\"init.mp4\"", theAddress),
        )
    }

    @Test
    fun `comments are dropped and an address line is read without the space around it`() {
        assertEquals(
            "#EXTM3U\nhttps://door.home:8443/library/1/seg-1.ts",
            aRule()?.rewrite("#EXTM3U\n# made by https://elsewhere.example\n  seg-1.ts\t", theAddress),
        )
        assertEquals("#EXT-X-ENDLIST", aRule()?.rewrite("#EXT-X-ENDLIST", theAddress))
    }
}
