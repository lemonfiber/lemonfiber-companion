import Testing

@testable import LemonfiberNative

// Which pages the app opens, and what asking for one came to.
//
// The same cases as `PageRuleTest.kt`, in the same order.

@Test("an https page the platform took is opened")
func anHttpsPageTakenIsOpened() {
    #expect(PageRule(address: "https://approve.example/link").said(pageWasAskedFor: true) == .opened)
}

@Test("an https page the platform would not take is refused")
func anHttpsPageNotTakenIsRefused() {
    #expect(PageRule(address: "https://approve.example/link").said(pageWasAskedFor: false) == .refused)
}

@Test("only an https address naming somewhere may be opened")
func onlyHttpsMayBeOpened() {
    #expect(PageRule(address: "HTTPS://approve.example").mayOpen)
    #expect(!PageRule(address: "http://approve.example").mayOpen)
    #expect(!PageRule(address: "intent://approve.example").mayOpen)
    #expect(!PageRule(address: "https://").mayOpen)
    #expect(!PageRule(address: "https://approve.example/a b").mayOpen)
    #expect(!PageRule(address: "https://approve.example/\n").mayOpen)
    #expect(!PageRule(address: "").mayOpen)
}

@Test("an address that may not be opened is refused even where the platform took it")
func aRefusedAddressIsRefused() {
    #expect(PageRule(address: "http://approve.example").said(pageWasAskedFor: true) == .refused)
}

@Test("the words are the wire's")
func theWordsAreTheWires() {
    #expect(WhetherThePageOpened.opened.word == "opened")
    #expect(WhetherThePageOpened.refused.word == "refused")
}
