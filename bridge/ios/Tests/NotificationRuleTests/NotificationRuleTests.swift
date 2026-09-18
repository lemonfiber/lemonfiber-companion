import Testing

@testable import LemonfiberNative

// What telling somebody adds to the answer every permission gives.
//
// The three states and the order they are read in belong to
// `WhatTheOperatorSaidTests`, because the camera asks the same question and two
// copies of that decision would be two capabilities disagreeing quietly. What is
// asked here is the part that is this capability's: that the four facts a
// notification is judged on reach that reading unshuffled, and that the answer
// is readable in the words a caller with something to show uses.
//
// The same six cases as `NotificationRuleTest.kt`, in the same order.

/// The unasked state with one fact changed.
///
/// Kotlin's `data class` gives this away in a `copy()`; a Swift `struct` with
/// `let` properties does not, and writing four arguments out at every call site
/// would make the one that differs the hardest thing on the line to find.
private func unasked(
    wouldAppear: Bool = false,
    permissionIsAsked: Bool = true,
    wouldExplain: Bool = false,
    everAsked: Bool = false
) -> NotificationRule {
    NotificationRule(
        wouldAppear: wouldAppear,
        permissionIsAsked: permissionIsAsked,
        wouldExplain: wouldExplain,
        everAsked: everAsked
    )
}

@Test("a notification that would appear may be shown")
func appearingMayBeShown() {
    let allowed = unasked(wouldAppear: true)

    #expect(allowed.said == .granted)
    #expect(allowed.mayShow)
    #expect(!allowed.mayAsk)
}

@Test("a refusal is not a reason to show anything")
func refusalShowsNothing() {
    let refused = unasked(everAsked: true)

    #expect(refused.said == .denied)
    #expect(!refused.mayShow)
    #expect(!refused.mayAsk)
}

@Test("nobody asked is a question still open and nothing to show yet")
func theUnaskedNotificationState() {
    // The one state that must answer "ask me". Without it the rule could report
    // a refusal unconditionally, every other case here would still pass, and an
    // application that never asks anybody anything would ship.
    #expect(NotificationRule.unasked.said == .notDetermined)
    #expect(NotificationRule.unasked.mayAsk)
    #expect(!NotificationRule.unasked.mayShow)
}

@Test("each fact reaches the reading it belongs to")
func everyFactArrivesWhereItBelongs() {
    // The shuffle this catches: four booleans gathered in one place and handed
    // on in another is an argument order that compiles whichever way round it is
    // written. Each fact is moved on its own, and each one changes the answer
    // differently.
    #expect(unasked(wouldAppear: true).said == .granted)
    #expect(unasked(permissionIsAsked: false).said == .denied)
    #expect(unasked(wouldExplain: true).said == .denied)
    #expect(unasked(everAsked: true).said == .denied)
}

@Test("a first launch on a platform that asks has asked nothing")
func theFirstLaunch() {
    #expect(
        NotificationRule.unasked
            == NotificationRule(
                wouldAppear: false,
                permissionIsAsked: true,
                wouldExplain: false,
                everAsked: false
            )
    )
}

@Test("an older platform has been answered rather than left open")
func anOlderPlatform() {
    // Below Android 33 there is no notification permission to prompt for, so
    // notifications off is a decision taken in settings. Carried as an input
    // rather than a version check inside the rule, which is what lets the case
    // be asked at all off a handset.
    let old = unasked(permissionIsAsked: false)

    #expect(!old.mayAsk)
    #expect(!old.mayShow)
}
