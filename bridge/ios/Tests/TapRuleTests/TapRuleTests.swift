import Testing

@testable import LemonfiberNative

// Where a tap on a notification may lead.
//
// The same six cases as `TapRuleTest.kt`, in the same order.

@Test("a link into the app is kept")
func aLinkIntoTheAppIsKept() {
    #expect(TapRule.opens("lemonfiber://stacks/abc123") == "lemonfiber://stacks/abc123")
}

@Test("a web page is not opened")
func aWebPageIsNotOpened() {
    #expect(TapRule.opens("https://example.org/stacks/abc123") == nil)
}

@Test("another app's scheme is not opened")
func anotherAppsSchemeIsNotOpened() {
    #expect(TapRule.opens("otherapp://stacks/abc123") == nil)
}

@Test("a link naming no screen is not opened")
func aLinkNamingNoScreenIsNotOpened() {
    #expect(TapRule.opens("lemonfiber://") == nil)
}

@Test("a link carrying credentials or a port is not opened")
func aLinkCarryingCredentialsOrAPortIsNotOpened() {
    #expect(TapRule.opens("lemonfiber://someone:secret@stacks/abc123") == nil)
    #expect(TapRule.opens("lemonfiber://stacks:8080/abc123") == nil)
}

@Test("nothing leads nowhere")
func nothingLeadsNowhere() {
    #expect(TapRule.opens(nil) == nil)
    #expect(TapRule.opens("") == nil)
    #expect(TapRule.opens("not a link") == nil)
}
