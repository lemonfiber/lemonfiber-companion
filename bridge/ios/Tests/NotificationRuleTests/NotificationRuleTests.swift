import XCTest

@testable import LemonfiberNative

/// Telling *nobody has been asked* from *the operator said no*.
///
/// The same cases as `NotificationRuleTest.kt`, in the same order. Keeping them
/// aligned is the point: the failure these two files exist to catch is the
/// platforms quietly disagreeing about whether somebody has already refused.
final class NotificationRuleTests: XCTestCase {
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

    func testANotificationThatWouldAppearIsGrantedWhateverElseIsTrue() {
        XCTAssertEqual(rule(wouldAppear: true).said(), "granted")
        XCTAssertEqual(rule(wouldAppear: true, wouldExplain: true, everAsked: true).said(), "granted")
    }

    func testNobodyAskedYetIsNotARefusal() {
        // The state that must not read as denied. Without it the app would
        // never raise its first prompt, and every other case here would still
        // pass — an app that silently never asks looks exactly like an app
        // whose operator refused.
        XCTAssertEqual(rule().said(), "not_determined")
    }

    func testAnExplanationThePlatformWouldOfferIsEvidenceOfARefusal() {
        XCTAssertEqual(rule(wouldExplain: true).said(), "denied")
    }

    func testAskedOnceAndStillSilentIsARefusal() {
        // The permanent refusal. Android answers this identically to never
        // having asked, which is the whole reason the asking is written down.
        XCTAssertEqual(rule(everAsked: true).said(), "denied")
    }

    func testSilenceWhereThereIsNothingToAskForIsARefusalAlreadyGiven() {
        XCTAssertEqual(rule(permissionIsAsked: false).said(), "denied")
        XCTAssertEqual(rule(permissionIsAsked: false, everAsked: false).said(), "denied")
    }
}
