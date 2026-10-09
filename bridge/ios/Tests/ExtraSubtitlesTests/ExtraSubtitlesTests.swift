import Testing

@testable import LemonfiberNative

// Subtitles an extension offers, joined into a stream's master playlist.
//
// The same cases as `ExtraSubtitlesTest.kt`, in the same order.

private let english = ExtraTrack(
    id: "en", kind: .subtitle, language: "en", label: "English", address: "https://door.home/subs/en.vtt")
private let dutch = ExtraTrack(
    id: "nl", kind: .subtitle, language: "nl", label: "Nederlands", address: "https://door.home/subs/nl.vtt")
private let commentary = ExtraTrack(
    id: "c", kind: .audio, language: "en", label: "Commentary", address: "https://door.home/c.m4a")

private let master = "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=1\nlfdoor://door.home/v1.m3u8\n"

@Test("a master playlist gains each subtitle file as a rendition of a group of its own")
func aMasterGainsRenditions() {
    #expect(
        ExtraSubtitles([english, commentary, dutch]).join(master)
            == "#EXTM3U\n"
            + "#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID=\"lf-extra\",NAME=\"English\",LANGUAGE=\"en\",AUTOSELECT=NO,DEFAULT=NO,URI=\"lfextra://track/0\"\n"
            + "#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID=\"lf-extra\",NAME=\"Nederlands\",LANGUAGE=\"nl\",AUTOSELECT=NO,DEFAULT=NO,URI=\"lfextra://track/1\"\n"
            + "#EXT-X-STREAM-INF:BANDWIDTH=1,SUBTITLES=\"lf-extra\"\nlfdoor://door.home/v1.m3u8\n")
}

@Test("a rendition joins the subtitle group the variants already name")
func aRenditionJoinsTheNamedGroup() {
    let named =
        "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=1,NAME=\"SUBTITLES=x\",SUBTITLES=\"subs\"\nv1.m3u8\n"
        + "#EXT-X-STREAM-INF:BANDWIDTH=2\nv2.m3u8"

    #expect(
        ExtraSubtitles([english]).join(named)
            == "#EXTM3U\n"
            + "#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID=\"subs\",NAME=\"English\",LANGUAGE=\"en\",AUTOSELECT=NO,DEFAULT=NO,URI=\"lfextra://track/0\"\n"
            + "#EXT-X-STREAM-INF:BANDWIDTH=1,NAME=\"SUBTITLES=x\",SUBTITLES=\"subs\"\nv1.m3u8\n"
            + "#EXT-X-STREAM-INF:BANDWIDTH=2,SUBTITLES=\"subs\"\nv2.m3u8")
}

@Test("a media playlist or a stream with nothing to join is handed back as it was")
func nothingToJoinIsHandedBack() {
    let media = "#EXTM3U\n#EXTINF:6.0,\nseg-1.ts\n"

    #expect(ExtraSubtitles([english]).join(media) == media)
    #expect(ExtraSubtitles([]).join(master) == master)
    #expect(ExtraSubtitles([commentary]).join(master) == master)
    #expect(ExtraSubtitles([english]).join("\n" + master) == "\n" + master)
    #expect(ExtraSubtitles([english]).join("#EXTM3U\n#EXT-X-STREAM-INF") == "#EXTM3U\n#EXT-X-STREAM-INF")
}

@Test("a label loses what a quoted value cannot hold and a language is written only as a plain tag")
func labelsAndLanguagesAreMadeSafe() {
    let odd = ExtraTrack(
        id: "x", kind: .subtitle, language: "en-GB", label: "Say \"hi\"\n\tnow, très",
        address: "https://door.home/x.vtt")
    let bare = ExtraTrack(
        id: "y", kind: .subtitle, language: "e n", label: "", address: "https://door.home/y.vtt")
    let joined = ExtraSubtitles([odd, bare]).join(master)

    #expect(joined.contains("NAME=\"Say hinow, très\",LANGUAGE=\"en-GB\","))
    #expect(joined.contains("NAME=\"Subtitles 2\",AUTOSELECT=NO"))
    #expect(!joined.contains("LANGUAGE=\"e n\""))
    #expect(
        !ExtraSubtitles([ExtraTrack(id: "z", kind: .subtitle, language: "", label: "Z", address: "x")]).join(
            master
        )
        .contains("LANGUAGE"))
}

@Test("a handed address names a joined track only by its place")
func aHandedAddressNamesATrackByItsPlace() {
    let extras = ExtraSubtitles([english, commentary, dutch])

    #expect(extras.track(at: ExtraSubtitles.address(of: 0)) == english)
    #expect(extras.track(at: "lfextra://track/1") == dutch)
    #expect(extras.track(at: "lfextra://track/2") == nil)
    #expect(extras.track(at: "lfextra://track/+1") == nil)
    #expect(extras.track(at: "lfextra://track/") == nil)
    #expect(extras.track(at: "lfdoor://track/0") == nil)
}

@Test("a joined track's playlist plays its file for as long as anything plays")
func aJoinedTracksPlaylistPlaysItsFile() {
    #expect(
        ExtraSubtitles.playlist(playing: "lfdoor://door.home/subs/en.vtt")
            == "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:86400\n#EXT-X-MEDIA-SEQUENCE:0\n"
            + "#EXT-X-PLAYLIST-TYPE:VOD\n#EXTINF:86400,\nlfdoor://door.home/subs/en.vtt\n#EXT-X-ENDLIST\n")
}
