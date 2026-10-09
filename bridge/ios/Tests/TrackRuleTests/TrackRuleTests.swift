import Testing

@testable import LemonfiberNative

// Which sound and which subtitles to start with.
//
// The same cases as `TrackRuleTest.kt`, in the same order.

private let english = OfferedTrack(id: "a1", language: "en-GB", label: "English", isDefault: false)
private let dutch = OfferedTrack(id: "a2", language: "nl", label: "Nederlands", isDefault: true)
private let unsaid = OfferedTrack(id: "a3", language: "", label: "Track 3", isDefault: false)

@Test("the member's language is played, matched without its region")
func theMembersLanguageIsPlayed() {
    #expect(TrackRule.audio(offered: [dutch, english], preferred: "EN") == "a1")
    #expect(TrackRule.audio(offered: [dutch, english], preferred: "en_US") == "a1")
}

@Test("without a match the stream's default plays, and without one the first")
func withoutAMatchTheDefaultPlays() {
    #expect(TrackRule.audio(offered: [english, dutch], preferred: "fr") == "a2")
    #expect(TrackRule.audio(offered: [unsaid, english], preferred: "") == "a3")
    #expect(TrackRule.audio(offered: [], preferred: "en") == nil)
}

@Test("subtitles in the member's language are shown")
func subtitlesInTheMembersLanguageAreShown() {
    #expect(TrackRule.subtitle(offered: [dutch, english], preferred: "nl-BE") == "a2")
}

@Test("subtitles are never forced on")
func subtitlesAreNeverForcedOn() {
    // No preference, an explicit off, and a language the stream does not
    // carry all answer none, never the stream's default.
    #expect(TrackRule.subtitle(offered: [dutch, english], preferred: "") == nil)
    #expect(TrackRule.subtitle(offered: [dutch, english], preferred: TrackRule.off) == nil)
    #expect(TrackRule.subtitle(offered: [dutch, english], preferred: "fr") == nil)
}
