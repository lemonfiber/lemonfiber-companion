import Testing

@testable import LemonfiberNative

// When the player reports how far it got, and when it stops waiting.
//
// The same cases as `PlaybackRuleTest.kt`, in the same order.

@Test("progress is reported every ten seconds while playing")
func progressIsReportedEveryTenSeconds() {
    #expect(!PlaybackRule.reportsProgress(sinceLastReport: 9.9, isPlaying: true, moved: false))
    #expect(PlaybackRule.reportsProgress(sinceLastReport: 10, isPlaying: true, moved: false))
}

@Test("nothing is reported while nothing moves")
func nothingIsReportedWhileNothingMoves() {
    #expect(!PlaybackRule.reportsProgress(sinceLastReport: 60, isPlaying: false, moved: false))
}

@Test("a pause, a seek, the end or a close is reported at once")
func aMoveIsReportedAtOnce() {
    #expect(PlaybackRule.reportsProgress(sinceLastReport: 0, isPlaying: false, moved: true))
}

@Test("a stall is waited out for ten seconds and no longer")
func aStallIsWaitedOutForTenSeconds() {
    #expect(!PlaybackRule.givesUp(stalledFor: 9.9))
    #expect(PlaybackRule.givesUp(stalledFor: 10))
}

@Test("a door's refusal, an unservable format and anything else are told apart")
func whyPlaybackStoppedIsToldApart() {
    for refused in [401, 403, 404, 410] {
        #expect(PlaybackRule.why(status: refused) == .refused)
    }

    #expect(PlaybackRule.why(status: 415) == .unsupportedFormat)
    #expect(PlaybackRule.why(status: 502) == .unreachable)
    #expect(PlaybackRule.why(status: 0) == .unreachable)
}

@Test("each reason is one word on the wire")
func eachReasonIsOneWord() {
    #expect(WhyPlaybackStopped.unreachable.word == "unreachable")
    #expect(WhyPlaybackStopped.pinMismatch.word == "pin_mismatch")
    #expect(WhyPlaybackStopped.unsupportedFormat.word == "unsupported_format")
    #expect(WhyPlaybackStopped.refused.word == "refused")
}
