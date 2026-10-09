import Foundation
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

@Test("an address with a name before the host is not at the door wherever the at sign falls")
func aNameBeforeTheHostIsNeverTheDoor() {
    let door = Door.of(theLocation)

    #expect(door?.holds("https://door.home:8443@elsewhere.example/library") == false)
    #expect(door?.holds("https://elsewhere.example@door.home:8443/library") == false)
    #expect(door?.holds("https://door.home%40elsewhere.example:8443/library") == false)
    #expect(Door.of("https://elsewhere.example@door.home/library") == nil)
}

@Test("a host written with escapes, other scripts or a stray dot or hyphen names no door")
func aHostOutsideTheGrammarNamesNoDoor() {
    #expect(Door.of("https://do%6Fr.home/library") == nil)
    #expect(Door.of("https://dóor.home/library") == nil)
    #expect(Door.of("https://door.home./library") == nil)
    #expect(Door.of("https://door..home/library") == nil)
    #expect(Door.of("https://-door.home/library") == nil)
    #expect(Door.of("https://door-.home/library") == nil)
    #expect(Door.of("https://door_1.home/library") == nil)
    #expect(Door.of("https://[::1]:8443/library") == nil)
}

@Test("an address with a backslash, a space or a control character names no door")
func anAddressWithInvisibleOrAmbiguousCharactersNamesNoDoor() {
    #expect(Door.of("https://door.home\\@elsewhere.example/library") == nil)
    #expect(Door.of("https://door.home\\elsewhere.example/library") == nil)
    #expect(Door.of("https://door.home/a library") == nil)
    #expect(Door.of("https://door.home/library\t") == nil)
    #expect(Door.of("https://door.home/library\u{7F}") == nil)
}

@Test("a port is read only as the plain number a connection is made to")
func aPortIsAPlainNumber() {
    #expect(Door.of("https://door.home:/library") == nil)
    #expect(Door.of("https://door.home:0443/library") == nil)
    #expect(Door.of("https://door.home:0/library") == nil)
    #expect(Door.of("https://door.home:65536/library") == nil)
    #expect(Door.of("https://door.home:123456/library") == nil)
    #expect(Door.of("https://door.home:84a3/library") == nil)
    #expect(Door.of("https://door.home:8443:1/library") == nil)
    #expect(Door.of("https://door.home:65535/library")?.port == 65_535)
    #expect(Door.of("https://192.168.1.10:8096/library")?.host == "192.168.1.10")
}

@Test("the address handed to the platform is the one that was checked")
func theAddressFetchedIsTheAddressChecked() {
    let door = Door.of(theLocation)
    let admitted = door?.admitted("HTTPS://Door.Home:8443/library/2/main.m3u8?part=1")

    #expect(admitted?.host?.lowercased() == "door.home")
    #expect(admitted?.port == 8443)
    #expect(admitted?.absoluteString == "HTTPS://Door.Home:8443/library/2/main.m3u8?part=1")
    #expect(door?.admitted("https://door.home:9443/library") == nil)
    #expect(door?.admitted("https:door.home:8443/library") == nil)
    #expect(door?.admitted("https:/door.home:8443/library") == nil)
}

@Test("the grammar reads only an https authority of a plain host and number")
func theGrammarReadsOnlyAPlainAuthority() {
    #expect(Door.authority(of: "https://Door.Home:8443/library")?.host == "door.home")
    #expect(Door.authority(of: "https://Door.Home:8443/library")?.port == 8443)
    #expect(Door.authority(of: "https://door.home/library")?.port == nil)
    #expect(Door.authority(of: "https://door.home/library")?.host == "door.home")
    #expect(Door.authority(of: "http://door.home/library") == nil)
    #expect(Door.authority(of: "https:door.home/library") == nil)
    #expect(Door.authority(of: "https://door.home/library\\a") == nil)
    #expect(Door.authority(of: "https://door.home:8443:1/library") == nil)
    #expect(Door.authority(of: "https://door.home:+443/library") == nil)
    #expect(Door.authority(of: "https://door.home/a library") == nil)
    #expect(Door.authority(of: "https://-door.home/library") == nil)
    #expect(Door.authority(of: "https://door-.home/library") == nil)
    #expect(Door.authority(of: "https://door_1.home/library") == nil)
}

@Test("the platform's reading must agree with the grammar's on scheme, name, host and port")
func thePlatformsReadingMustAgree() {
    let written = (host: "door.home", port: Int?.some(8443))

    #expect(Door.agrees(URL(string: "https://door.home:8443/library")!, with: written))
    #expect(!Door.agrees(URL(string: "http://door.home:8443/library")!, with: written))
    #expect(!Door.agrees(URL(string: "https://member@door.home:8443/library")!, with: written))
    #expect(!Door.agrees(URL(string: "https://:secret@door.home:8443/library")!, with: written))
    #expect(!Door.agrees(URL(string: "https://elsewhere.example:8443/library")!, with: written))
    #expect(!Door.agrees(URL(string: "https://door.home/library")!, with: written))
}
