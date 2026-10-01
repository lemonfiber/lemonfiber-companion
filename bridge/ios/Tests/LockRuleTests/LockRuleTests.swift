import Testing

@testable import LemonfiberNative

// When the app is locked, and what the glass may show.
//
// The same cases as `LockRuleTest.kt`; a CI job compares the two files case
// for case, so the halves cannot quietly disagree about when an app locks.

private let later: Int64 = 1_000

private func openAt(_ now: Int64) -> LockRule {
    LockRule.coldStart().asking().answered(succeeded: true).left(now)
}

@Test("a cold start stands, whatever Lock after says")
func coldStartStands() {
    #expect(LockRule.coldStart().standsAt(now: 0, canAsk: true))
    #expect(LockRule.coldStart().awayFor(seconds: 3600).standsAt(now: later, canAsk: true))
}

@Test("only the device's success opens it")
func onlySuccessOpens() {
    let failed = LockRule.coldStart().asking().answered(succeeded: false)

    #expect(failed.held)
    #expect(!failed.prompting)
    #expect(!LockRule.coldStart().asking().answered(succeeded: true).held)
}

@Test("coming back inside Lock after leaves it open")
func insideLockAfterStaysOpen() {
    let rule = openAt(later).awayFor(seconds: 60).returned(now: later + 59, canAsk: true)

    #expect(!rule.held)
    #expect(!rule.mustCover)
}

@Test("coming back once Lock after has passed stands it again")
func pastLockAfterStands() {
    let rule = openAt(later).awayFor(seconds: 60).returned(now: later + 60, canAsk: true)

    #expect(rule.held)
    #expect(rule.mustCover)
}

@Test("immediately stands it on every return")
func immediatelyStandsEveryTime() {
    #expect(openAt(later).returned(now: later, canAsk: true).held)
}

@Test("a time away counts before the return")
func timeAwayCountsBeforeTheReturn() {
    let away = openAt(later).awayFor(seconds: 60)

    #expect(!away.standsAt(now: later + 59, canAsk: true))
    #expect(away.standsAt(now: later + 60, canAsk: true))
}

@Test("the device's own prompt going up is not leaving")
func promptIsNotLeaving() {
    let rule = LockRule.coldStart().asking().answered(succeeded: true).asking().left(later)

    #expect(!rule.away)
    #expect(!rule.returned(now: later + 3600, canAsk: true).held)
}

@Test("the glass stays covered from leaving until the lock is drawn")
func coveredUntilDrawn() {
    let back = openAt(later).returned(now: later, canAsk: true)

    #expect(openAt(later).mustCover)
    #expect(back.mustCover)
    #expect(!back.drawn().mustCover)
}

@Test("opening it uncovers the glass")
func openingUncovers() {
    let back = openAt(later).returned(now: later, canAsk: true)

    #expect(!back.asking().answered(succeeded: true).mustCover)
    #expect(back.asking().answered(succeeded: false).mustCover)
}

@Test("it asks by itself once per standing")
func asksByItselfOnce() {
    let asked = openAt(later).returned(now: later, canAsk: true).askingByItself().answered(succeeded: false)

    #expect(!asked.mayAskByItself)
    #expect(asked.left(later).returned(now: later, canAsk: true).held)
    #expect(!asked.left(later).returned(now: later, canAsk: true).mayAskByItself)
    #expect(
        asked.asking().answered(succeeded: true).left(later).returned(now: later, canAsk: true).mayAskByItself
    )
}

@Test("it never asks over a prompt that is already up")
func neverAsksOverAPrompt() {
    #expect(!LockRule.coldStart().asking().mayAskByItself)
}

@Test("waiving opens it and uncovers the glass")
func waivingOpens() {
    let waived = openAt(later).returned(now: later, canAsk: true).waived()

    #expect(!waived.held)
    #expect(!waived.mustCover)
}

@Test("a device with no screen lock has nobody to ask")
func noScreenLockNobodyToAsk() {
    #expect(!LockRule.coldStart().standsAt(now: 0, canAsk: false))
    #expect(!openAt(later).returned(now: later, canAsk: false).mustCover)
}

@Test("the passcode stands behind the biometrics")
func passcodeBehindBiometrics() {
    #expect(WhatUnlocks.accepted == .deviceOwnerAuthentication)
}
