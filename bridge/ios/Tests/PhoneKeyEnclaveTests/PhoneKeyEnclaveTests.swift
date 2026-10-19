import Foundation
import Testing

@testable import LemonfiberNative

// How the public half the Secure Enclave exports is spelt for the stack, and how an error is read.

@Test("the public half is the P-256 point behind its SubjectPublicKeyInfo header")
func thePublicHalfIsSpeltAsTheStackReadsIt() {
    let point: [UInt8] = [0x04] + Array(repeating: 0xAB, count: 64)
    let spelt = PhoneKeyRule.publicKeyInfo(point: point)

    #expect(spelt?.count == 91)
    #expect(Array(spelt?.prefix(26) ?? []) == PhoneKeyRule.spkiHeader)
    #expect(Array(spelt?.suffix(65) ?? []) == point)
}

@Test("anything but an uncompressed P-256 point is not spelt at all")
func aStrangePointIsNotSpelt() {
    #expect(PhoneKeyRule.publicKeyInfo(point: [0x02] + Array(repeating: 0xAB, count: 64)) == nil)
    #expect(PhoneKeyRule.publicKeyInfo(point: [0x04] + Array(repeating: 0xAB, count: 32)) == nil)
}

@Test("a code is read in its own domain")
func aCodeIsReadInItsOwnDomain() {
    #expect(
        PhoneKeyRule.whyNotSigned(domain: NSOSStatusErrorDomain, code: PhoneKeyRule.promptSystemCancel)
            == .failed)
    #expect(
        PhoneKeyRule.whyNotSigned(domain: PhoneKeyRule.promptDomain, code: Int(PhoneKeyRule.itemNotFound))
            == .failed)
    #expect(PhoneKeyRule.whyNotSigned(domain: "", code: 0) == .failed)
}
