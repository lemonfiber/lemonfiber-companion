import Testing

@testable import LemonfiberNative

// The one decision the iOS half makes, held to both requirements that want it.
//
// Written as a table rather than as four `#expect`s because the interesting
// property is the shape of the whole truth table: three of the four states
// protect, and the single state that does not is the ordinary one. A reader
// checking this against N4-R9 and N4-R18 is checking a grid, so the test is a
// grid.

@Test("N4-R9 — a backgrounded app is protected, whatever it was showing")
func backgroundedIsAlwaysProtected() {
    #expect(CaptureRule(concealed: false, foreground: false).mustProtect)
    #expect(CaptureRule(concealed: true, foreground: false).mustProtect)
}

@Test("N4-R18 — a guarded screen is protected while it is in front of you")
func concealedInForegroundIsProtected() {
    // The case a rule written as `concealed && !foreground` gets wrong, and the
    // reason that expression is not repeated at call sites. A screen recording
    // runs while the app is the thing you are looking at, so this is exactly
    // when the protection is needed and exactly when a careless reading drops it.
    #expect(CaptureRule(concealed: true, foreground: true).mustProtect)
}

@Test("an ordinary screen in front of you is not protected")
func ordinaryForegroundIsNotProtected() {
    // The one state that must *not* protect. Without this the rule could return
    // `true` unconditionally and every other test here would still pass — and a
    // build that refuses every screenshot forever would ship.
    #expect(!CaptureRule(concealed: false, foreground: true).mustProtect)
}

@Test("the app launches in front of somebody, showing nothing guarded")
func launchedIsTheOrdinaryState() {
    #expect(CaptureRule.launched == CaptureRule(concealed: false, foreground: true))
    #expect(!CaptureRule.launched.mustProtect)
}

@Test("concealing survives the app going away and coming back")
func concealmentOutlivesBackgrounding() {
    // The sequence that actually happens: a guarded screen is open, the operator
    // takes a call, and comes back. If `foregrounded()` cleared concealment the
    // screen would be recordable on return, with nothing on screen to say so.
    let returned = CaptureRule.launched
        .concealing()
        .backgrounded()
        .foregrounded()

    #expect(returned.concealed)
    #expect(returned.mustProtect)
}

@Test("leaving a guarded screen while backgrounded stays protected")
func revealingWhileAwayKeepsTheCover() {
    // Revealing takes away N4-R18's reason and leaves N4-R9's. The window must
    // still be protected, because the app is still in the task switcher.
    let away = CaptureRule.launched.concealing().backgrounded().revealing()

    #expect(!away.concealed)
    #expect(away.mustProtect)
}
