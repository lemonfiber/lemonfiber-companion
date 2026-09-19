import Testing

@testable import LemonfiberNative

// The distinction handing something over turns on.
//
// Not whether the operator sent the report — this cannot know that and must not
// — but whether the sheet was ever put in front of them, and why not where it
// was not. The two refusals have different remedies and arrive as the same
// nothing.
//
// The same five cases as `HandoverRuleTest.kt`, in the same order.

@Test("a report that is there and a sheet that opened refuses nothing")
func anOfferedHandoverRefusesNothing() {
    #expect(HandoverRule.offered.wasOffered)
    #expect(HandoverRule.offered.whyNot == nil)
}

@Test("nothing to hand over is not a platform that would not")
func nothingToHandOverIsNotAShutPlatform() {
    // Both false, which is the combination that proves the order. A rule asking
    // about the sheet first would send somebody to try again at a thing that
    // fails the same way every time.
    let missing = HandoverRule(thereIsSomethingToHandOver: false, theSheetWasPresented: false)

    #expect(!missing.wasOffered)
    #expect(missing.whyNot == .nothingToHandOver)
}

@Test("a report that is there and a sheet that would not open says so")
func aShutSheetSaysSo() {
    let shut = HandoverRule(thereIsSomethingToHandOver: true, theSheetWasPresented: false)

    #expect(!shut.wasOffered)
    #expect(shut.whyNot == .thePlatformWouldNot)
}

@Test("a missing report is refused even where the sheet would have opened")
func aMissingReportIsRefusedRegardless() {
    let missing = HandoverRule(thereIsSomethingToHandOver: false, theSheetWasPresented: true)

    #expect(!missing.wasOffered)
    #expect(missing.whyNot == .nothingToHandOver)
}

@Test("each refusal is one word on the wire")
func eachRefusalIsOneWord() {
    #expect(WhyNothingWasHandedOver.nothingToHandOver.word == "nothing_to_hand_over")
    #expect(WhyNothingWasHandedOver.thePlatformWouldNot.word == "the_platform_would_not")
}
