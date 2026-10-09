import Testing

@testable import LemonfiberNative

// An HLS playlist, read so that every address in it stays at the door.
//
// The same cases as `PlaylistRuleTest.kt`, in the same order.

private let theAddress = "https://door.home:8443/library/1/main.m3u8"

private func aRule(_ scheme: String = "https") -> PlaylistRule? {
    Door.of(theAddress).map { PlaylistRule(door: $0, scheme: scheme) }
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
