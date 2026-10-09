import Testing

@testable import LemonfiberNative

// What the player says when the app asks where it is.
//
// The same cases as `PlayerStateTest.kt`, in the same order.

private let english = OfferedTrack(id: "a1", language: "en", label: "English", isDefault: true)
private let dutch = OfferedTrack(id: "s1", language: "nl", label: "Nederlands", isDefault: false)

@Test("a state is answered in closed words and numbers, with every track")
func aStateIsAnsweredInClosedWords() {
    let said = PlayerState(
        stands: .playing, position: 61.5, duration: 5400, audio: [english], subtitles: [dutch],
        chosenAudio: "a1", chosenSubtitle: "s1", why: nil
    ).answer()

    #expect(said["stands"] as? String == "playing")
    #expect(said["position"] as? Double == 61.5)
    #expect(said["duration"] as? Double == 5400)
    #expect(
        (said["audio"] as? [[String: String]]) == [["id": "a1", "language": "en", "label": "English"]])
    #expect(
        (said["subtitles"] as? [[String: String]]) == [["id": "s1", "language": "nl", "label": "Nederlands"]])
    #expect(said["chosen_audio"] as? String == "a1")
    #expect(said["chosen_subtitle"] as? String == "s1")
}

@Test("a player that stopped says why, and one that did not says nothing")
func aStoppedPlayerSaysWhy() {
    let stopped = PlayerState(
        stands: .stopped, position: 12, duration: 5400, audio: [], subtitles: [], chosenAudio: nil,
        chosenSubtitle: nil, why: .unreachable)

    #expect(stopped.answer()["why"] as? String == "unreachable")
    #expect(PlayerState.closed.answer()["why"] as? String == "")
}

@Test("nothing chosen is answered as empty")
func nothingChosenIsEmpty() {
    let said = PlayerState.closed.answer()

    #expect(said["stands"] as? String == "closed")
    #expect(said["chosen_audio"] as? String == "")
    #expect(said["chosen_subtitle"] as? String == "")
}

@Test("each place playback stands is one word on the wire")
func eachPlaceIsOneWord() {
    #expect(WherePlaybackStands.opening.word == "opening")
    #expect(WherePlaybackStands.playing.word == "playing")
    #expect(WherePlaybackStands.paused.word == "paused")
    #expect(WherePlaybackStands.stalled.word == "stalled")
    #expect(WherePlaybackStands.ended.word == "ended")
    #expect(WherePlaybackStands.stopped.word == "stopped")
    #expect(WherePlaybackStands.closed.word == "closed")
}
