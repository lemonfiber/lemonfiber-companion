import Testing

@testable import LemonfiberNative

// The origin every request the player makes must stay at.
//
// The same cases as `DoorTest.kt`, in the same order.

private let theLocation = "https://door.home:8443/library/1/main.m3u8"

@Test("a door is named only by an https address with a host and no credentials")
func aDoorIsNamedOnlyByACleanHttpsAddress() {
    #expect(Door.of(theLocation)?.host == "door.home")
    #expect(Door.of(theLocation)?.port == 8443)
    #expect(Door.of("http://door.home/library/1/main.m3u8") == nil)
    #expect(Door.of("https://member:secret@door.home/library/1/main.m3u8") == nil)
    #expect(Door.of("https:///library/1/main.m3u8") == nil)
    #expect(Door.of("not an address") == nil)
}

@Test("a door with no port named is at the https port")
func aDoorWithNoPortIsAtTheHttpsPort() {
    #expect(Door.of("https://Door.Home/library")?.port == 443)
    #expect(Door.of("https://door.home:443/elsewhere")?.holds("https://DOOR.home/library") == true)
}

@Test("an address at another host, port or scheme is not at the door")
func anotherOriginIsNotAtTheDoor() {
    let door = Door.of(theLocation)

    #expect(door?.holds("https://door.home:8443/library/2/main.m3u8") == true)
    #expect(door?.holds("https://elsewhere.example:8443/library/2/main.m3u8") == false)
    #expect(door?.holds("https://door.home:9443/library/2/main.m3u8") == false)
    #expect(door?.holds("http://door.home:8443/library/2/main.m3u8") == false)
    #expect(door?.holds("https://member@door.home:8443/library/2/main.m3u8") == false)
}

@Test("an address a document names is made absolute against the document")
func anAddressIsMadeAbsolute() {
    let door = Door.of(theLocation)

    #expect(door?.resolve("seg-1.ts", against: theLocation) == "https://door.home:8443/library/1/seg-1.ts")
    #expect(
        door?.resolve("/library/2/main.m3u8", against: theLocation)
            == "https://door.home:8443/library/2/main.m3u8")
    #expect(
        door?.resolve("seg-2.ts?part=2", against: theLocation)
            == "https://door.home:8443/library/1/seg-2.ts?part=2")
    #expect(
        door?.resolve("https://door.home:8443/library/3/a.m3u8", against: theLocation)
            == "https://door.home:8443/library/3/a.m3u8")
}

@Test("an address a document names off the door resolves to nothing")
func anAddressOffTheDoorResolvesToNothing() {
    let door = Door.of(theLocation)

    #expect(door?.resolve("https://elsewhere.example/seg-1.ts", against: theLocation) == nil)
    #expect(door?.resolve("//elsewhere.example/seg-1.ts", against: theLocation) == nil)
    #expect(door?.resolve("seg-1.ts", against: "not an address at all ::") == nil)
}
