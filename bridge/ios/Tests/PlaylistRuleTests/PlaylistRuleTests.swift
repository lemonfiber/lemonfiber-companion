import Testing

@testable import LemonfiberNative

// An HLS playlist, read so that every address in it stays at the door.
//
// The same cases as `PlaylistRuleTest.kt`, in the same order.

private let theAddress = "https://door.home:8443/library/1/main.m3u8"

private func aRule(_ scheme: String = "https") -> PlaylistRule? {
    Door.of(theAddress).map { PlaylistRule(door: $0, handing: { scheme + $0.dropFirst(Door.scheme.count) }) }
}

@Test("a media playlist's segments are made absolute at the door")
func segmentsAreMadeAbsolute() {
    let playlist = "#EXTM3U\n#EXTINF:6.0,\nseg-1.ts\n#EXTINF:6.0,\n/library/1/seg-2.ts\n"

    #expect(
        aRule()?.rewrite(playlist, at: theAddress)
            == "#EXTM3U\n#EXTINF:6.0,\nhttps://door.home:8443/library/1/seg-1.ts\n#EXTINF:6.0,\nhttps://door.home:8443/library/1/seg-2.ts\n"
    )
}

@Test("every address a tag names is rewritten, from the key and the map to a rendition")
func everyTagAddressIsRewritten() {
    let playlist = """
        #EXT-X-KEY:METHOD=AES-128,URI="key.bin",IV=0x1
        #EXT-X-MAP:URI="init.mp4"
        #EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID="subs",LANGUAGE="en",URI="subs/en.m3u8"
        """

    #expect(
        aRule()?.rewrite(playlist, at: theAddress)
            == """
            #EXT-X-KEY:METHOD=AES-128,URI="https://door.home:8443/library/1/key.bin",IV=0x1
            #EXT-X-MAP:URI="https://door.home:8443/library/1/init.mp4"
            #EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID="subs",LANGUAGE="en",URI="https://door.home:8443/library/1/subs/en.m3u8"
            """)
}

@Test("a playlist naming anything off the door is refused whole")
func aPlaylistOffTheDoorIsRefused() {
    #expect(
        aRule()?.rewrite("#EXTINF:6.0,\nseg-1.ts\nhttps://elsewhere.example/seg-2.ts", at: theAddress) == nil)
    #expect(aRule()?.rewrite("#EXT-X-KEY:METHOD=SAMPLE-AES,URI=\"skd://key\"", at: theAddress) == nil)
    #expect(aRule()?.rewrite("#EXT-X-MAP:URI=\"init.mp4", at: theAddress) == nil)
}

@Test("addresses are handed over under the scheme the player reads them by")
func addressesAreHandedUnderThePlayersScheme() {
    #expect(
        aRule("lfdoor")?.rewrite("seg-1.ts\n#EXT-X-MAP:URI=\"init.mp4\"", at: theAddress)
            == "lfdoor://door.home:8443/library/1/seg-1.ts\n#EXT-X-MAP:URI=\"lfdoor://door.home:8443/library/1/init.mp4\""
    )
}

@Test("tags without addresses and blank lines pass through as they were")
func tagsAndBlankLinesPassThrough() {
    let playlist = "#EXTM3U\n\n#EXT-X-VERSION:7\n#EXT-X-TARGETDURATION:6"

    #expect(aRule()?.rewrite(playlist, at: theAddress) == playlist)
}

@Test("lines ending in a carriage return are read as lines")
func carriageReturnsAreReadAsLines() {
    #expect(
        aRule()?.rewrite("#EXTM3U\r\nseg-1.ts\r\n", at: theAddress)
            == "#EXTM3U\nhttps://door.home:8443/library/1/seg-1.ts\n")
}

@Test("a quoted value holding a comma is one value of one attribute")
func aQuotedCommaIsOneValue() {
    let playlist =
        "#EXT-X-STREAM-INF:BANDWIDTH=1,CODECS=\"avc1.64001f,mp4a.40.2\"\nv.m3u8\n"
        + "#EXT-X-MEDIA:TYPE=AUDIO,NAME=\"English, described\",URI=\"a/en.m3u8\""

    #expect(
        aRule()?.rewrite(playlist, at: theAddress)
            == "#EXT-X-STREAM-INF:BANDWIDTH=1,CODECS=\"avc1.64001f,mp4a.40.2\"\nhttps://door.home:8443/library/1/v.m3u8\n"
            + "#EXT-X-MEDIA:TYPE=AUDIO,NAME=\"English, described\",URI=\"https://door.home:8443/library/1/a/en.m3u8\""
    )
}

@Test("every attribute that names an address is rewritten, interstitials and steering among them")
func everyAddressAttributeIsRewritten() {
    let playlist = """
        #EXT-X-I-FRAME-STREAM-INF:BANDWIDTH=1,URI="i.m3u8"
        #EXT-X-SESSION-KEY:METHOD=AES-128,URI="k.bin"
        #EXT-X-PRELOAD-HINT:TYPE=PART,URI="p.mp4"
        #EXT-X-CONTENT-STEERING:SERVER-URI="steer.json"
        #EXT-X-DATERANGE:ID="ad",X-ASSET-URI="ad.m3u8",X-ASSET-LIST="ads.json"
        """

    #expect(
        aRule()?.rewrite(playlist, at: theAddress)
            == """
            #EXT-X-I-FRAME-STREAM-INF:BANDWIDTH=1,URI="https://door.home:8443/library/1/i.m3u8"
            #EXT-X-SESSION-KEY:METHOD=AES-128,URI="https://door.home:8443/library/1/k.bin"
            #EXT-X-PRELOAD-HINT:TYPE=PART,URI="https://door.home:8443/library/1/p.mp4"
            #EXT-X-CONTENT-STEERING:SERVER-URI="https://door.home:8443/library/1/steer.json"
            #EXT-X-DATERANGE:ID="ad",X-ASSET-URI="https://door.home:8443/library/1/ad.m3u8",X-ASSET-LIST="https://door.home:8443/library/1/ads.json"
            """)
}

@Test("an absolute address left in any other value refuses the playlist")
func anAbsoluteAddressElsewhereIsRefused() {
    #expect(
        aRule()?.rewrite(
            "#EXT-X-SESSION-DATA:DATA-ID=\"x\",VALUE=\"https://elsewhere.example/a\"", at: theAddress) == nil)
    #expect(
        aRule()?.rewrite("#EXT-X-SESSION-DATA:DATA-ID=\"x\",VALUE=https:elsewhere.example", at: theAddress)
            == nil)
    #expect(
        aRule()?.rewrite("#EXT-X-SESSION-DATA:DATA-ID=\"x\",VALUE=\"//elsewhere.example/a\"", at: theAddress)
            == nil)
    #expect(
        aRule()?.rewrite("#EXT-X-SESSION-DATA:DATA-ID=\"x\",VALUE=\"\\\\\\\\elsewhere\\\\a\"", at: theAddress)
            == nil)
    #expect(aRule()?.rewrite("#EXTINF:6.0,https://elsewhere.example/a", at: theAddress) == nil)
    #expect(
        aRule()?.rewrite("#EXT-X-MAP:URI=https://door.home:8443/library/1/init.mp4", at: theAddress) == nil)
    #expect(aRule()?.rewrite("#EXT-X-MAP:URI=\"\"", at: theAddress) == nil)
    #expect(aRule()?.rewrite("#EXT-X-MAP:URI=init.mp4", at: theAddress) == nil)
    #expect(
        aRule()?.rewrite(
            "#EXT-X-SESSION-DATA:DATA-ID=\"x\",VALUE=\"see https://elsewhere.example\"", at: theAddress)
            == nil)
    #expect(aRule()?.rewrite("#EXT-X-SESSION-DATA:DATA-ID=\"x\",VALUE=\"unterminated", at: theAddress) == nil)
    #expect(aRule()?.rewrite("#EXT-X-MAP:URI=\"", at: theAddress) == nil)
    #expect(aRule()?.rewrite("#EXT-X-SESSION-DATA:DATA-ID=\"x\",VALUE=\"seg-1.ts\"", at: theAddress) != nil)
}

@Test("a value that only looks like an address attribute is read by the attribute it belongs to")
func aMisleadingValueIsReadByItsAttribute() {
    #expect(
        aRule()?.rewrite(
            "#EXT-X-MEDIA:NAME=\"URI=\",URI=\"https://elsewhere.example/a.m3u8\"", at: theAddress) == nil
    )
    #expect(
        aRule()?.rewrite("#EXT-X-MEDIA:NAME=\"URI=\",URI=\"a.m3u8\"", at: theAddress)
            == "#EXT-X-MEDIA:NAME=\"URI=\",URI=\"https://door.home:8443/library/1/a.m3u8\"")
    #expect(aRule()?.rewrite("#EXT-X-MEDIA:\"URI=a.m3u8\",URI=\"a.m3u8\"", at: theAddress) != nil)
    #expect(
        aRule()?.rewrite("#EXT-X-MEDIA:NAME=\"x,URI=seg.ts\",URI=\"a.m3u8\"", at: theAddress)
            == "#EXT-X-MEDIA:NAME=\"x,URI=seg.ts\",URI=\"https://door.home:8443/library/1/a.m3u8\"")
}

@Test("a playlist that defines or uses variables is refused")
func variablesAreRefused() {
    #expect(aRule()?.rewrite("#EXT-X-DEFINE:NAME=\"base\",VALUE=\"seg\"", at: theAddress) == nil)
    #expect(aRule()?.rewrite("#ext-x-define:NAME=\"base\"", at: theAddress) == nil)
    #expect(aRule()?.rewrite("#EXTINF:6.0,\n{$base}-1.ts", at: theAddress) == nil)
    #expect(aRule()?.rewrite("#EXT-X-SESSION-DATA:DATA-ID=\"{$name}\"", at: theAddress) == nil)
}

@Test("a tag or attribute written in lower case is read all the same")
func lowerCaseIsReadAllTheSame() {
    #expect(aRule()?.rewrite("#ext-x-map:uri=\"https://elsewhere.example/init.mp4\"", at: theAddress) == nil)
    #expect(
        aRule()?.rewrite("#ext-x-map:uri=\"init.mp4\"", at: theAddress)
            == "#ext-x-map:uri=\"https://door.home:8443/library/1/init.mp4\"")
}

@Test("comments are dropped and an address line is read without the space around it")
func commentsAreDroppedAndAddressesTrimmed() {
    #expect(
        aRule()?.rewrite("#EXTM3U\n# made by https://elsewhere.example\n  seg-1.ts\t", at: theAddress)
            == "#EXTM3U\nhttps://door.home:8443/library/1/seg-1.ts")
    #expect(aRule()?.rewrite("#EXT-X-ENDLIST", at: theAddress) == "#EXT-X-ENDLIST")
}
