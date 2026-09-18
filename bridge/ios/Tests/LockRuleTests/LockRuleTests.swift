import Testing

@testable import LemonfiberNative

// When the app must be locked, and when it may ask.
//
// The same cases as `LockRuleTest.kt`, in the same order, for the same reason
// the capture rule is mirrored: two platforms quietly disagreeing about when an
// app locks is a bug nobody finds, because each half looks right on its own. A
// CI job compares the two files case for case.

@Test("a cold start is locked, whatever the grace period says")
func coldStartIsLocked() {
    // No grace across a launch. The app that was open before is not the app
    // that is open now, and an hour of grace configured yesterday must not
    // carry a relaunch today.
    #expect(LockRule.coldStart(grace: 3600).mustLock)
    #expect(LockRule.coldStart(grace: 0).mustLock)
}

@Test("an app idle past its grace period is locked")
func idlePastGraceIsLocked() {
    let rule = LockRule.coldStart(grace: 60).authenticated().after(seconds: 60)

    #expect(rule.mustLock)
}

@Test("an app inside its grace period is not locked")
func insideGraceIsNotLocked() {
    // The one state that must *not* lock. Without it the rule could answer true
    // unconditionally, every other test here would still pass, and an app that
    // demanded a passcode on every glance would ship.
    let rule = LockRule.coldStart(grace: 60).authenticated().after(seconds: 59)

    #expect(!rule.mustLock)
    #expect(!rule.mayPrompt)
}

@Test("a grace of nothing means asking every time")
func zeroGraceAlwaysLocks() {
    // `>=` rather than `>` is what makes this work, and it is the boundary the
    // comparison gets wrong in the other direction: with `>`, a grace of zero
    // would never lock at all — the configuration meaning "ask every time"
    // would mean "never ask".
    let rule = LockRule.coldStart(grace: 0).authenticated()

    #expect(rule.mustLock)
}

@Test("no prompt while the operator is in the middle of something")
func noPromptDuringAnAction() {
    // Locked, and silent. The lock screen is shown; the device's own prompt
    // waits. An operator interrupted mid-action answers a dialog to get rid of
    // it, which is not authentication.
    let rule = LockRule.coldStart(grace: 60).doing()

    #expect(rule.mustLock)
    #expect(!rule.mayPrompt)
}

@Test("the prompt comes once the action is finished")
func promptsOnceTheActionIsDone() {
    let rule = LockRule.coldStart(grace: 60).doing().idle()

    #expect(rule.mustLock)
    #expect(rule.mayPrompt)
}

@Test("authenticating clears the lock and the clock")
func authenticatingClearsBoth() {
    let rule = LockRule.coldStart(grace: 60).after(seconds: 500).authenticated()

    #expect(!rule.mustLock)
    #expect(rule.secondsSinceAuthenticated == 0)
}
