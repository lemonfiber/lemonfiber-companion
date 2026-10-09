import Foundation
import Testing

@testable import LemonfiberNative

// Whether a certificate the door presents is the one the core said it would.
//
// The certificates are stand-in bytes: the rule hashes whatever it is handed,
// and what it decides is whether that hash is the pinned one.
//
// The same cases as `DoorPinTest.kt`, in the same order.

private let theDoorsCertificate = Data("the door's certificate".utf8)
private let anotherMachinesCertificate = Data("another machine's certificate".utf8)
private let theDoorsFingerprint = "13ee3a6685a324f7ebbfebb922980ba07a74c0e54cf2243b2f199dc81912e9f5"

@Test("the certificate the door promised is admitted")
func thePromisedCertificateIsAdmitted() {
    #expect(DoorPin.of(theDoorsFingerprint)?.admits(leaf: theDoorsCertificate) == true)
}

@Test("any other certificate is refused")
func anyOtherCertificateIsRefused() {
    #expect(DoorPin.of(theDoorsFingerprint)?.admits(leaf: anotherMachinesCertificate) == false)
}

@Test("a fingerprint in capitals pins the same certificate")
func capitalsPinTheSameCertificate() {
    #expect(DoorPin.of(theDoorsFingerprint.uppercased())?.admits(leaf: theDoorsCertificate) == true)
}

@Test("a fingerprint that is not a digest pins nothing")
func aFingerprintThatIsNotADigestPinsNothing() {
    // Short, long, empty, a colon where a pair should be, and a character no
    // hexadecimal digit is: each is a malformed answer, and none may become a
    // pin that admits anything.
    for malformed in [
        String(theDoorsFingerprint.dropLast()),
        theDoorsFingerprint + "0",
        "",
        "13:" + String(theDoorsFingerprint.dropFirst(3)),
        "g" + String(theDoorsFingerprint.dropFirst()),
        String(theDoorsFingerprint.dropLast()) + "g",
    ] {
        #expect(DoorPin.of(malformed) == nil)
    }
}

@Test("a fingerprint is sixty-four characters")
func aFingerprintIsSixtyFourCharacters() {
    #expect(DoorPin.characters == 64)
}
