import Testing

@testable import LemonfiberNative

// Telling *nobody has been asked* from *the operator said no*.
//
// The same cases as `NotificationRuleTest.kt`, in the same order. Keeping them
// aligned is the point: what these two files exist to catch is the platforms
// quietly disagreeing about whether somebody has already refused, and a
// disagreement is only visible if the questions are the same.

private func rule(
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

@Test("a notification that would appear is granted, whatever else is true")
func wouldAppearIsGranted() {
    // First question and usually the last. It covers the runtime permission,
    // the channel switch and the app switch at once, and any of them being off
    // means the same thing to somebody waiting to be told something.
    #expect(rule(wouldAppear: true).said() == "granted")
    #expect(rule(wouldAppear: true, wouldExplain: true, everAsked: true).said() == "granted")
}

@Test("nobody asked yet is not a refusal")
func neverAskedIsNotDetermined() {
    // The state that must not read as denied. Without it the app would never
    // raise its first prompt, and every other case here would still pass — an
    // app that silently never asks looks exactly like one whose operator
    // refused.
    #expect(rule().said() == "not_determined")
}

@Test("an explanation the platform would offer is evidence of a refusal")
func wouldExplainIsDenied() {
    // True only after a refusal, which is what makes it evidence of one.
    #expect(rule(wouldExplain: true).said() == "denied")
}

@Test("asked once and still silent is a refusal")
func everAskedAndSilentIsDenied() {
    // The permanent refusal. Android answers this identically to never having
    // asked, which is why the asking is written down rather than inferred.
    #expect(rule(everAsked: true).said() == "denied")
}

@Test("silence where there is nothing to ask for is a refusal already given")
func noRuntimePermissionIsDenied() {
    // Below Android 33 there is no runtime permission. Notifications off means
    // the operator turned them off in settings, and prompting for a permission
    // the platform does not have would do nothing at all.
    #expect(rule(permissionIsAsked: false).said() == "denied")
    #expect(rule(permissionIsAsked: false, everAsked: false).said() == "denied")
}
