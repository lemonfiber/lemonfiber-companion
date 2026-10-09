import Foundation
import Testing

@testable import LemonfiberNative

// Whether a connection the player opened reached the door the core stated.
//
// The same cases as `DoorTrustTest.kt`, in the same order.

private let theDoorsCertificate = Data("the door's certificate".utf8)
private let theDoorsFingerprint = "13ee3a6685a324f7ebbfebb922980ba07a74c0e54cf2243b2f199dc81912e9f5"

private func trust() -> DoorTrust? {
    guard let door = Door.of("https://door.home:8443/library"), let pin = DoorPin.of(theDoorsFingerprint)
    else {
        return nil
    }

    return DoorTrust(door: door, pin: pin)
}

@Test("the promised certificate at the door is admitted")
func thePromisedCertificateAtTheDoorIsAdmitted() {
    #expect(trust()?.admits(leaf: theDoorsCertificate, host: "Door.Home", port: 8443) == true)
}

@Test("the promised certificate at another host or port is refused")
func thePromisedCertificateElsewhereIsRefused() {
    #expect(trust()?.admits(leaf: theDoorsCertificate, host: "elsewhere.example", port: 8443) == false)
    #expect(trust()?.admits(leaf: theDoorsCertificate, host: "door.home", port: 443) == false)
}

@Test("another certificate at the door is refused")
func anotherCertificateAtTheDoorIsRefused() {
    #expect(
        trust()?.admits(leaf: Data("another machine's certificate".utf8), host: "door.home", port: 8443)
            == false)
}

@Test("a connection that presented no certificate is refused")
func noCertificateIsRefused() {
    #expect(trust()?.admits(leaf: nil, host: "door.home", port: 8443) == false)
}
