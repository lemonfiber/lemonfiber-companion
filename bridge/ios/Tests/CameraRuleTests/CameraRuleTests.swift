import Testing

@testable import LemonfiberNative

// What reading a code adds to the answer every permission gives.
//
// The three states and the order they are read in belong to
// `WhatTheOperatorSaidTests`, which is shared with telling somebody. What is
// asked here is the part that is this capability's: that a missing lens is told
// from a refused one, and that a refusal the platform would still reconsider is
// told from a settled one — which are two different sentences on a screen and
// the reason this rule exists at all.
//
// The same seven cases as `CameraRuleTest.kt`, in the same order.

/// The unasked state with one fact changed.
///
/// Kotlin's `data class` gives this away in a `copy()`; a Swift `struct` with
/// `let` properties does not, and writing four arguments out at every call site
/// would make the one that differs the hardest thing on the line to find.
private func unasked(
    cameraExists: Bool = true,
    permissionIsGranted: Bool = false,
    wouldExplain: Bool = false,
    everAsked: Bool = false
) -> CameraRule {
    CameraRule(
        cameraExists: cameraExists,
        permissionIsGranted: permissionIsGranted,
        wouldExplain: wouldExplain,
        everAsked: everAsked
    )
}

@Test("a granted camera may be opened without asking anybody")
func grantedCameraMayOpen() {
    let allowed = unasked(permissionIsGranted: true)

    #expect(allowed.said == .granted)
    #expect(allowed.mayOpen)
    #expect(!allowed.mayAsk)
    #expect(!allowed.mayAskAgain)
    #expect(allowed.whyNot == nil)
}

@Test("no camera beats a permission that was granted")
func noCameraBeatsAGrant() {
    // Granted *and* absent, which is the combination that proves the lens is
    // read first. A rule that asked about the permission first would report a
    // device with no camera as ready to scan.
    let absent = unasked(cameraExists: false, permissionIsGranted: true)

    #expect(!absent.mayOpen)
    #expect(!absent.mayAsk)
    #expect(!absent.mayAskAgain)
    #expect(absent.whyNot == .thereIsNoCamera)
}

@Test("nobody asked is a question still open rather than a refusal")
func nobodyAskedIsNotARefusal() {
    // The one state that must answer "ask me". Without it the rule could report
    // a refusal unconditionally, every other case here would still pass, and a
    // scanner that never asks anybody anything would ship.
    #expect(CameraRule.unasked.said == .notDetermined)
    #expect(CameraRule.unasked.mayAsk)
    #expect(CameraRule.unasked.mayAskAgain)
    #expect(!CameraRule.unasked.mayOpen)
    #expect(CameraRule.unasked.whyNot == nil)
}

@Test("a refusal the platform would still explain can be put again")
func aRefusalStillWorthExplaining() {
    let declined = unasked(wouldExplain: true, everAsked: true)

    #expect(declined.said == .denied)
    #expect(!declined.mayOpen)
    #expect(!declined.mayAsk)
    #expect(declined.mayAskAgain)
    #expect(declined.whyNot == .theCameraIsNotPermitted)
}

@Test("a settled refusal sends the operator to settings instead")
func aSettledRefusal() {
    // The same refusal word and the opposite advice, which is the whole reason
    // `mayAskAgain` is a separate reading: a screen choosing between "try again"
    // and "open Settings" has only this to choose on.
    let settled = unasked(everAsked: true)

    #expect(settled.said == .denied)
    #expect(!settled.mayAskAgain)
    #expect(settled.whyNot == .theCameraIsNotPermitted)
}

@Test("the operator closing it is never a permission's answer")
func closingIsNeverAPermissionsAnswer() {
    // Over the whole grid rather than over one case. Closing the scanner is what
    // happened while it was open, and a rule that could answer it from four
    // booleans would be reporting a dismissal nobody performed.
    let grid = everyCombination()

    #expect(grid.count == 16)

    for rule in grid {
        #expect(rule.whyNot != .theOperatorClosedIt)
    }
}

/// Every combination of the four facts, as sixteen rules.
///
/// Built from pairs rather than from four nested loops, for the reason the
/// Kotlin half is: four loops deep, the reader has to hold which of the four a
/// name belongs to.
private func everyCombination() -> [CameraRule] {
    let both = [false, true]
    let pairs = both.flatMap { first in both.map { second in (first, second) } }

    return pairs.flatMap { exists, granted in
        pairs.map { explain, asked in
            CameraRule(
                cameraExists: exists,
                permissionIsGranted: granted,
                wouldExplain: explain,
                everAsked: asked
            )
        }
    }
}

@Test("each way of getting nothing has its own word on the wire")
func everyRefusalHasAWord() {
    #expect(WhyNothingWasRead.theOperatorClosedIt.word == "the_operator_closed_it")
    #expect(WhyNothingWasRead.theCameraIsNotPermitted.word == "the_camera_is_not_permitted")
    #expect(WhyNothingWasRead.thereIsNoCamera.word == "there_is_no_camera")
}
