import Testing

@testable import LemonfiberNative

// Three answers reconstructed from the two a platform is willing to report.
//
// The same eight cases as `WhatTheOperatorSaidTest.kt`, in the same order.
// Keeping them aligned is the point: the failure these two files exist to catch
// is the platforms quietly disagreeing about whether somebody has been asked,
// and a disagreement is only visible if the questions are the same.
//
// iOS needs none of the reconstruction — `UNAuthorizationStatus` and
// `AVAuthorizationStatus` separate the three themselves — and the cases are
// asked here anyway, against the same inputs. A reading tested on one platform
// only cannot be seen to disagree with the other.

@Test("something that would work settles it")
func workingSettlesIt() {
    // First and unconditional. An operator who refused once and turned it back
    // on in settings has allowed it, and every other input still says otherwise
    // — so a reading in any other order reports a refusal about a device that is
    // working right now.
    let allowedAfterRefusing = WhatTheOperatorSaid.readFrom(
        wouldAppear: true,
        permissionIsAsked: true,
        wouldExplain: true,
        everAsked: true
    )

    #expect(allowedAfterRefusing == .granted)
}

@Test("a platform with no permission to ask has been answered in settings")
func nothingToAskFor() {
    // Below Android 33 there is no runtime permission for notifications and
    // nothing to prompt for. Off is a decision already taken rather than a
    // question still open, and asking would raise a dialog the platform has
    // never heard of. iOS never reaches this state and answers the case anyway.
    #expect(
        WhatTheOperatorSaid.readFrom(
            wouldAppear: false,
            permissionIsAsked: false,
            wouldExplain: false,
            everAsked: false
        ) == .denied
    )
}

@Test("an explanation the platform would show is evidence of a refusal")
func rationaleIsEvidence() {
    // `shouldShowRequestPermissionRationale` is true only between a first
    // refusal and a permanent one, which makes it the one positive signal
    // Android gives that somebody has said no.
    #expect(
        WhatTheOperatorSaid.readFrom(
            wouldAppear: false,
            permissionIsAsked: true,
            wouldExplain: true,
            everAsked: false
        ) == .denied
    )
}

@Test("a prompt this app has already raised was answered no")
func everAskedIsARefusal() {
    // The case nothing in the platform can answer. Once a refusal is permanent
    // the rationale flag goes false again, so *refused for good* and *never
    // asked* are reported identically — and only a record kept where the prompt
    // was raised tells them apart.
    #expect(
        WhatTheOperatorSaid.readFrom(
            wouldAppear: false,
            permissionIsAsked: true,
            wouldExplain: false,
            everAsked: true
        ) == .denied
    )
}

@Test("nobody has been asked until somebody has")
func theUnaskedState() {
    // The one state that must answer "ask me". Without it the reading could
    // report a refusal unconditionally, every other case here would still pass,
    // and an application that never asks anybody anything would ship.
    #expect(
        WhatTheOperatorSaid.readFrom(
            wouldAppear: false,
            permissionIsAsked: true,
            wouldExplain: false,
            everAsked: false
        ) == .notDetermined
    )
}

@Test("asking and proceeding are different questions")
func askingIsNotProceeding() {
    // Both false about a refusal, for opposite reasons, which is why neither is
    // the negation of the other. A caller that read one for the other would
    // prompt somebody who has already refused — on every screen, forever.
    #expect(!WhatTheOperatorSaid.denied.mayAsk)
    #expect(!WhatTheOperatorSaid.denied.mayProceed)
}

@Test("a granted answer is not one to ask about again")
func grantedIsNotAQuestion() {
    #expect(WhatTheOperatorSaid.granted.mayProceed)
    #expect(!WhatTheOperatorSaid.granted.mayAsk)
    #expect(WhatTheOperatorSaid.notDetermined.mayAsk)
    #expect(!WhatTheOperatorSaid.notDetermined.mayProceed)
}

@Test("every answer carries the word the wire uses")
func theWordsAreTheContract() {
    // The words are the contract. A misspelling here is a `default` arm on the
    // other side of the bridge, which reads a refusal as somebody who has not
    // been asked and re-opens a prompt they already answered.
    #expect(WhatTheOperatorSaid.granted.word == "granted")
    #expect(WhatTheOperatorSaid.denied.word == "denied")
    #expect(WhatTheOperatorSaid.notDetermined.word == "not_determined")
}
