import Testing

@testable import LemonfiberNative

// The distinction the local-network probe turns on.
//
// A refused permission and a machine that is off are the same silence at the
// socket. The platform says which, and only the refusal is read.
//
// The same five cases as `LocalNetworkRuleTest.kt`, in the same order.

@Test("a path the platform let be taken is open")
func aPermittedPathIsOpen() {
    #expect(LocalNetworkRule.permitted.said == .open)
}

@Test("a path the platform denied for the permission is forbidden")
func aDeniedPathIsRefused() {
    let denied = LocalNetworkRule(pathWasReadable: true, platformDeniedIt: true)

    #expect(denied.said == .forbidden)
}

@Test("a platform that said nothing is read as open")
func anUnaskablePlatformIsOpen() {
    // Every machine that is not a handset: the app then reports the stack as
    // not answering, which is what it always did.
    #expect(LocalNetworkRule.unasked.said == .open)
}

@Test("having said nothing wins over a denial nobody heard")
func silenceWins() {
    let unread = LocalNetworkRule(pathWasReadable: false, platformDeniedIt: true)

    #expect(unread.said == .open)
}

@Test("each answer is one word on the wire")
func eachAnswerIsOneWord() {
    #expect(WhetherTheLocalNetworkIsOpen.open.word == "open")
    #expect(WhetherTheLocalNetworkIsOpen.forbidden.word == "forbidden")
}
