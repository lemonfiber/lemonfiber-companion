import Foundation
import Testing

@testable import LemonfiberNative

// Whether this phone may keep a key, and the words for what became of one.
//
// The same eight cases as `PhoneKeyRuleTest.kt`. Spelling the public half and reading an error by its
// domain are iOS's alone and are asked in `PhoneKeyEnclaveTests`.

@Test("a key lives in hardware where the phone has some and a face or fingerprint guards it")
func aKeyLivesInTheEnclave() {
    #expect(PhoneKeyRule.mayLive(enclave: true, biometric: true) == .lives(.enclave))
}

@Test("no face or fingerprint is told from a phone that cannot keep a key at all")
func noBiometricIsNotNoHardware() {
    #expect(PhoneKeyRule.mayLive(enclave: true, biometric: false) == .not(.noBiometric))
    #expect(PhoneKeyRule.mayLive(enclave: false, biometric: true) == .not(.noHardware))
}

@Test("somebody declining is not a failure")
func somebodyDecliningIsNotAFailure() {
    let keychain = NSOSStatusErrorDomain
    let prompt = PhoneKeyRule.promptDomain

    #expect(PhoneKeyRule.whyNotSigned(domain: prompt, code: PhoneKeyRule.promptUserCancel) == .cancelled)
    #expect(PhoneKeyRule.whyNotSigned(domain: prompt, code: PhoneKeyRule.promptSystemCancel) == .cancelled)
    #expect(PhoneKeyRule.whyNotSigned(domain: prompt, code: PhoneKeyRule.promptAppCancel) == .cancelled)
    #expect(PhoneKeyRule.whyNotSigned(domain: keychain, code: Int(PhoneKeyRule.userCanceled)) == .cancelled)
    #expect(PhoneKeyRule.whyNotSigned(domain: keychain, code: Int(PhoneKeyRule.itemNotFound)) == .gone)
    #expect(PhoneKeyRule.whyNotSigned(domain: keychain, code: Int(PhoneKeyRule.authFailed)) == .failed)
    #expect(PhoneKeyRule.whyNotSigned(domain: prompt, code: -8) == .failed)
}

@Test("a key still there after it was thrown away is never said to be gone")
func aKeyStillThereIsNotGone() {
    #expect(PhoneKeyRule.thrownAway(status: PhoneKeyRule.authFailed) == .notForgotten)
    #expect(PhoneKeyRule.thrownAway(status: -34018) == .notForgotten)
}

@Test("a key that was never there is told from one thrown away")
func noKeyIsToldFromOneThrownAway() {
    #expect(PhoneKeyRule.thrownAway(status: PhoneKeyRule.success) == .forgotten)
    #expect(PhoneKeyRule.thrownAway(status: PhoneKeyRule.itemNotFound) == .noneHeld)
}

@Test("a phone is called what its owner knows it as")
func aPhoneIsCalledByItsModel() {
    #expect(PhoneKeyRule.modelName(identifier: "iPhone13,1") == "iPhone 12 mini")
}

@Test("a phone with no better name is called what the platform says it is")
func anUnknownPhoneIsAnIPhone() {
    #expect(PhoneKeyRule.modelName(identifier: "iPhone99,9") == PhoneKeyRule.anyIPhone)
    #expect(PhoneKeyRule.modelName(identifier: "") == "iPhone")
}

@Test("every word is the one the wire carries")
func everyWordIsTheWires() {
    #expect(WhereAPhoneKeyLives.allCases.map(\.word) == ["enclave"])
    #expect(
        WhyNoPhoneKey.allCases.map(\.word) == [
            "no_hardware", "no_biometric", "in_software", "unattested", "failed",
        ])
    #expect(WhyNothingWasSigned.allCases.map(\.word) == ["cancelled", "gone", "failed"])
    #expect(ThrownAway.allCases.map(\.word) == ["forgotten", "none_held", "not_forgotten"])
}
