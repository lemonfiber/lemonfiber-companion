import Foundation

/// What the operator has said about a permission, and what may be done about it.
///
/// Three answers rather than two, and the third is the whole point. *Nobody has
/// been asked* and *somebody said no* are the same answer to "may I do this"
/// and opposite answers to "may I ask" — an application that cannot tell them
/// apart either prompts somebody who already refused, every time they open the
/// screen, or never asks anybody at all.
///
/// **Its own file because more than one capability needs it.** Notifications
/// ask this question and so does the camera, and the three states are the same
/// three in both: a refusal in the dialog just now can be asked about again, a
/// refusal settled some time ago cannot, and nobody having been asked is
/// neither. Left inside one capability's rule the second would either import a
/// type named after something unrelated or — far more likely — declare its own
/// near-copy, and then two capabilities would quietly disagree about what
/// `denied` means.
///
/// The other reason is the wire. `outcome` and `because` are closed sets that
/// never carry a value the caller passed in, and a closed set kept in one place
/// is a promise something can be made to check; one copied into each capability
/// is a promise nothing can stand over.
///
/// The word is what crosses the wire. A number would be smaller and worse: a
/// log line reading `not_determined` explains itself to whoever is reading it,
/// and one reading `2` sends them to a file.
///
/// Deliberately mirrors `WhatTheOperatorSaid.kt` line for line.
public enum WhatTheOperatorSaid: String, Sendable {
    /// The thing the permission guards would work right now.
    case granted

    /// It would not, and nothing may ask again.
    case denied

    /// Nobody has been asked; the point of first use is still ahead.
    case notDetermined = "not_determined"

    /// What this answer is called on the wire.
    public var word: String { rawValue }

    /// Whether raising the prompt now could change the answer.
    ///
    /// Deliberately not the negation of `mayProceed`. A grant and a refusal are
    /// both reasons not to ask and opposite answers to whether anything may
    /// happen, so a caller reading one for the other is an application that
    /// prompts on every screen or never prompts at all.
    public var mayAsk: Bool { self == .notDetermined }

    /// Whether the thing the permission guards may happen now.
    public var mayProceed: Bool { self == .granted }

    /// The three answers, reconstructed from what a platform is willing to report.
    ///
    /// iOS reports all three itself — `UNAuthorizationStatus` and
    /// `AVAuthorizationStatus` each have a `.notDetermined` — and the
    /// reconstruction is written here anyway, taking the same four facts and
    /// ignoring the ones it does not need.
    ///
    /// That looks like waste and is the opposite. The failure these paired
    /// files exist to catch is the two platforms quietly disagreeing about
    /// whether somebody has already been asked, and a rule that exists on one
    /// side only cannot disagree visibly. The cost is two inputs iOS always
    /// answers the same way; what it buys is that a disagreement becomes a
    /// failing test rather than a support conversation.
    ///
    /// Read in this order and the order is load-bearing. Something that would
    /// work settles it whatever else is true: an operator who refused once and
    /// allowed it again in settings has allowed it. After that every remaining
    /// answer is a refusal of some kind, and the last case standing is the one
    /// nobody has answered yet.
    ///
    /// - Parameters:
    ///   - wouldAppear: whether the thing the permission guards would work now.
    ///   - permissionIsAsked: whether this platform has a runtime permission at all.
    ///   - wouldExplain: whether the platform says an explanation would help.
    ///   - everAsked: whether this application has ever raised the prompt.
    /// - Returns: what the operator has said, as far as anything can tell.
    public static func readFrom(
        wouldAppear: Bool,
        permissionIsAsked: Bool,
        wouldExplain: Bool,
        everAsked: Bool
    ) -> WhatTheOperatorSaid {
        if wouldAppear { return .granted }
        if !permissionIsAsked { return .denied }
        if wouldExplain { return .denied }
        if everAsked { return .denied }

        return .notDetermined
    }
}
