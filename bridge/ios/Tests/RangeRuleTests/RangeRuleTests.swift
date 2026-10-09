import Testing

@testable import LemonfiberNative

// Byte ranges, as the player asks for part of a file and checks what came back.
//
// The same cases as `RangeRuleTest.kt`, in the same order.

@Test("reading from the start to the end asks for no range")
func theWholeFileAsksForNoRange() {
    #expect(RangeRule.asked(offset: 0, length: nil) == nil)
}

@Test("reading from a byte asks for everything after it")
func readingFromAByteAsksForTheRest() {
    #expect(RangeRule.asked(offset: 1024, length: nil) == "bytes=1024-")
}

@Test("reading a length asks for exactly those bytes")
func readingALengthAsksForThoseBytes() {
    #expect(RangeRule.asked(offset: 0, length: 2) == "bytes=0-1")
    #expect(RangeRule.asked(offset: 100, length: 50) == "bytes=100-149")
}

@Test("the whole file's length is read off the content range where it says")
func theLengthIsReadOffTheContentRange() {
    #expect(RangeRule.total(contentRange: "bytes 0-99/1000") == 1000)
    #expect(RangeRule.total(contentRange: "bytes */1000") == 1000)
    #expect(RangeRule.total(contentRange: "bytes 0-99/*") == nil)
    #expect(RangeRule.total(contentRange: "bytes 0-99") == nil)
    #expect(RangeRule.total(contentRange: nil) == nil)
}

@Test("only the answer that was asked for is admitted")
func onlyTheAnswerAskedForIsAdmitted() {
    // A range answered with the whole file would hand the player the wrong
    // bytes at the right position.
    #expect(RangeRule.admits(status: 206, askedForPart: true))
    #expect(!RangeRule.admits(status: 200, askedForPart: true))
    #expect(RangeRule.admits(status: 200, askedForPart: false))
    #expect(!RangeRule.admits(status: 206, askedForPart: false))
    #expect(!RangeRule.admits(status: 500, askedForPart: false))
}
