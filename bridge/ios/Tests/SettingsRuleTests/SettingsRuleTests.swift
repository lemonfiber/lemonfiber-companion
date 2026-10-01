import Testing

@testable import LemonfiberNative

// What asking for the app's settings page came to.
//
// The same three cases as `SettingsRuleTest.kt`, in the same order.

@Test("a page the platform took the request for is opened")
func aTakenRequestIsOpened() {
    #expect(SettingsRule(pageWasAskedFor: true).said == .opened)
}

@Test("a page the platform would not open is refused")
func aRefusedRequestIsRefused() {
    #expect(SettingsRule(pageWasAskedFor: false).said == .refused)
}

@Test("the words on the wire are the ones the app reads")
func theWordsAreTheWire() {
    #expect(WhetherTheSettingsOpened.opened.word == "opened")
    #expect(WhetherTheSettingsOpened.refused.word == "refused")
}
