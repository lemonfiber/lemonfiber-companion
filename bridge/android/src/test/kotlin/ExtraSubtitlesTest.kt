package app.lemonfiber.native

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * Subtitles an extension offers, joined into a stream's master playlist.
 *
 * The same cases as `ExtraSubtitlesTests.swift`, in the same order.
 */
class ExtraSubtitlesTest {
    private val english =
        ExtraTrack("en", KindOfTrack.SUBTITLE, "en", "English", "https://door.home/subs/en.vtt")
    private val dutch =
        ExtraTrack("nl", KindOfTrack.SUBTITLE, "nl", "Nederlands", "https://door.home/subs/nl.vtt")
    private val commentary = ExtraTrack("c", KindOfTrack.AUDIO, "en", "Commentary", "https://door.home/c.m4a")
    private val master = "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=1\nlfdoor://door.home/v1.m3u8\n"
    private val media = "#EXT-X-MEDIA:TYPE=SUBTITLES"

    @Test
    fun `a master playlist gains each subtitle file as a rendition of a group of its own`() {
        assertEquals(
            "#EXTM3U\n" +
                "$media,GROUP-ID=\"lf-extra\",NAME=\"English\",LANGUAGE=\"en\",AUTOSELECT=NO,DEFAULT=NO," +
                "URI=\"lfextra://track/0\"\n" +
                "$media,GROUP-ID=\"lf-extra\",NAME=\"Nederlands\",LANGUAGE=\"nl\",AUTOSELECT=NO,DEFAULT=NO," +
                "URI=\"lfextra://track/1\"\n" +
                "#EXT-X-STREAM-INF:BANDWIDTH=1,SUBTITLES=\"lf-extra\"\nlfdoor://door.home/v1.m3u8\n",
            ExtraSubtitles(listOf(english, commentary, dutch)).join(master),
        )
    }

    @Test
    fun `a rendition joins the subtitle group the variants already name`() {
        val named =
            "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=1,NAME=\"SUBTITLES=x\",SUBTITLES=\"subs\"\nv1.m3u8\n" +
                "#EXT-X-STREAM-INF:BANDWIDTH=2\nv2.m3u8"

        assertEquals(
            "#EXTM3U\n" +
                "$media,GROUP-ID=\"subs\",NAME=\"English\",LANGUAGE=\"en\",AUTOSELECT=NO,DEFAULT=NO," +
                "URI=\"lfextra://track/0\"\n" +
                "#EXT-X-STREAM-INF:BANDWIDTH=1,NAME=\"SUBTITLES=x\",SUBTITLES=\"subs\"\nv1.m3u8\n" +
                "#EXT-X-STREAM-INF:BANDWIDTH=2,SUBTITLES=\"subs\"\nv2.m3u8",
            ExtraSubtitles(listOf(english)).join(named),
        )
    }

    @Test
    fun `a media playlist or a stream with nothing to join is handed back as it was`() {
        val playlist = "#EXTM3U\n#EXTINF:6.0,\nseg-1.ts\n"

        assertEquals(playlist, ExtraSubtitles(listOf(english)).join(playlist))
        assertEquals(master, ExtraSubtitles(emptyList()).join(master))
        assertEquals(master, ExtraSubtitles(listOf(commentary)).join(master))
        assertEquals("\n" + master, ExtraSubtitles(listOf(english)).join("\n" + master))
        assertEquals(
            "#EXTM3U\n#EXT-X-STREAM-INF",
            ExtraSubtitles(listOf(english)).join("#EXTM3U\n#EXT-X-STREAM-INF"),
        )
    }

    @Test
    fun `a label loses what a quoted value cannot hold and a language is written only as a plain tag`() {
        val odd =
            ExtraTrack(
                "x",
                KindOfTrack.SUBTITLE,
                "en-GB",
                "Say \"hi\"\n\tnow, très",
                "https://door.home/x.vtt",
            )
        val bare = ExtraTrack("y", KindOfTrack.SUBTITLE, "e n", "", "https://door.home/y.vtt")
        val joined = ExtraSubtitles(listOf(odd, bare)).join(master)

        assertTrue(joined.contains("NAME=\"Say hinow, très\",LANGUAGE=\"en-GB\","))
        assertTrue(joined.contains("NAME=\"Subtitles 2\",AUTOSELECT=NO"))
        assertFalse(joined.contains("LANGUAGE=\"e n\""))
        assertFalse(
            ExtraSubtitles(
                listOf(ExtraTrack("z", KindOfTrack.SUBTITLE, "", "Z", "x")),
            ).join(master).contains("LANGUAGE"),
        )
    }

    @Test
    fun `a handed address names a joined track only by its place`() {
        val extras = ExtraSubtitles(listOf(english, commentary, dutch))

        assertEquals(english, extras.track(ExtraSubtitles.address(0)))
        assertEquals(dutch, extras.track("lfextra://track/1"))
        assertNull(extras.track("lfextra://track/2"))
        assertNull(extras.track("lfextra://track/+1"))
        assertNull(extras.track("lfextra://track/"))
        assertNull(extras.track("lfdoor://track/0"))
    }

    @Test
    fun `a joined track's playlist plays its file for as long as anything plays`() {
        assertEquals(
            "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:86400\n#EXT-X-MEDIA-SEQUENCE:0\n" +
                "#EXT-X-PLAYLIST-TYPE:VOD\n#EXTINF:86400,\nlfdoor://door.home/subs/en.vtt\n#EXT-X-ENDLIST\n",
            ExtraSubtitles.playlist("lfdoor://door.home/subs/en.vtt"),
        )
    }
}
