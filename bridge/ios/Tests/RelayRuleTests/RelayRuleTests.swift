import Foundation
import Testing

@testable import LemonfiberNative

private func theRule() throws -> RelayRule {
    RelayRule(door: try #require(Door.of("https://door.home:8920/")), token: "a1b2", port: 49152)
}

@Test("an address at the door is handed at loopback under the playback's token, path and query as written")
func anAddressIsHandedAtLoopback() throws {
    let aRule = try theRule()

    #expect(
        aRule.handed("https://door.home:8920/Videos/1/master.m3u8?Codec=h264,hevc&Name=a%20b")
            == "http://127.0.0.1:49152/a1b2/Videos/1/master.m3u8?Codec=h264,hevc&Name=a%20b")
    #expect(aRule.handed("https://door.home:8920") == "http://127.0.0.1:49152/a1b2/")
    #expect(aRule.target(of: "https://door.home:8920/a.vtt") == "/a1b2/a.vtt")
}

@Test("an address off the door is handed nowhere")
func anAddressOffTheDoorIsHandedNowhere() throws {
    let aRule = try theRule()

    #expect(aRule.handed("https://elsewhere.home:8920/a.m3u8") == nil)
    #expect(aRule.handed("https://door.home:8921/a.m3u8") == nil)
    #expect(aRule.handed("http://door.home:8920/a.m3u8") == nil)
    #expect(aRule.target(of: "https://user@door.home:8920/a.m3u8") == nil)
}

@Test("a request under the playback's token asks for that path at the door")
func aRequestAsksForThatPathAtTheDoor() throws {
    let aRule = try theRule()

    #expect(
        aRule.asked("/a1b2/Videos/1/main.m3u8?x=1")?.absoluteString
            == "https://door.home:8920/Videos/1/main.m3u8?x=1")
    #expect(aRule.asked("/a1b2/")?.absoluteString == "https://door.home:8920/")
    #expect(aRule.asked("/a1b2/@elsewhere.home/x")?.host == "door.home")
    #expect(aRule.asked("/a1b2//elsewhere.home/x")?.host == "door.home")
}

@Test("a request under any other token, or none, asks for nothing")
func anotherTokenAsksForNothing() throws {
    let aRule = try theRule()

    #expect(aRule.asked("/a1b3/Videos/1/main.m3u8") == nil)
    #expect(aRule.asked("/a1b2") == nil)
    #expect(aRule.asked("/a1b2x/Videos") == nil)
    #expect(aRule.asked("/Videos/1/main.m3u8") == nil)
    #expect(aRule.asked("/a1b2/~extra/0") == nil)
}

@Test("an extra subtitle is asked for by its place, beneath the token")
func anExtraIsAskedForByItsPlace() throws {
    let aRule = try theRule()

    #expect(aRule.handedExtra(2) == "http://127.0.0.1:49152/a1b2/~extra/2")
    #expect(aRule.extra("/a1b2/~extra/2") == 2)
    #expect(aRule.extra("/a1b2/~extra/") == nil)
    #expect(aRule.extra("/a1b2/~extra/+2") == nil)
    #expect(aRule.extra("/a1b2/~extra/12345") == nil)
    #expect(aRule.extra("/zzzz/~extra/2") == nil)
    #expect(aRule.extra("/a1b2/Videos/1") == nil)
}

@Test("a joined master names each extra subtitle at the relay, the tenth apart from the first")
func aJoinedMasterNamesExtrasAtTheRelay() throws {
    let aRule = try theRule()

    let joined =
        "URI=\"" + ExtraSubtitles.address(of: 1) + "\"\nURI=\"" + ExtraSubtitles.address(of: 10) + "\""

    #expect(
        aRule.handingExtras(in: joined, count: 11)
            == "URI=\"http://127.0.0.1:49152/a1b2/~extra/1\"\nURI=\"http://127.0.0.1:49152/a1b2/~extra/10\"")
}

@Test("a request head is read for its method, target and range")
func aHeadIsRead() {
    #expect(
        RelayRule.read("GET /a1b2/x.mp4 HTTP/1.1\r\nHost: 127.0.0.1\r\nRANGE:  bytes=0-99 ")
            == RelayRule.Request(method: "GET", target: "/a1b2/x.mp4", range: "bytes=0-99"))
    #expect(
        RelayRule.read("HEAD /a1b2/x.mp4 HTTP/1.0")
            == RelayRule.Request(method: "HEAD", target: "/a1b2/x.mp4", range: nil))
}

@Test("a head the relay does not answer is read as nothing")
func anUnansweredHeadIsNothing() {
    #expect(RelayRule.read("POST /a1b2/x HTTP/1.1") == nil)
    #expect(RelayRule.read("GET a1b2/x HTTP/1.1") == nil)
    #expect(RelayRule.read("GET /a1b2/x HTTP/2") == nil)
    #expect(RelayRule.read("GET /a1b2/x") == nil)
    #expect(RelayRule.read("GET  /a1b2/x HTTP/1.1") == nil)
}

@Test("an answer's head names its status, its headers, and that the connection closes")
func anAnswersHeadIsWritten() {
    #expect(
        RelayRule.head(status: 206, headers: [("Content-Range", "bytes 0-9/10")])
            == "HTTP/1.1 206 Partial Content\r\nContent-Range: bytes 0-9/10\r\nConnection: close\r\n\r\n")
    #expect(RelayRule.head(status: 418, headers: []) == "HTTP/1.1 418 Status\r\nConnection: close\r\n\r\n")
}
