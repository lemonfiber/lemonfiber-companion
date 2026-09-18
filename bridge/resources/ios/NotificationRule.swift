import Foundation

/// What the device would do with a notification, read rather than asked.
///
/// The distinction this exists for is between *nobody has been asked* and *the
/// operator said no*. An app that cannot tell them apart prompts a second time
/// on a device where somebody already refused, which is the one thing the rule
/// about a declined permission forbids.
///
/// iOS answers the question directly — `UNAuthorizationStatus` has a
/// `.notDetermined` of its own — so the two states this has to reconstruct on
/// Android arrive here already separated. The rule is written anyway, and takes
/// the same four inputs, because the failure these files exist to catch is the
/// two platforms quietly disagreeing, and a rule that exists on one side only
/// cannot disagree visibly.
public struct NotificationRule: Equatable, Sendable {
    /// Whether a notification posted now would actually appear.
    ///
    /// The whole answer where it is true, and deliberately the first question:
    /// authorisation is not the only switch, and any of them being off means
    /// the same thing to somebody waiting to be told something.
    public let wouldAppear: Bool

    /// Whether this platform has an authorisation to ask for at all.
    ///
    /// Always true on iOS, where notifications have always required one. The
    /// input exists so that the two rules take the same shape; Android's answer
    /// is false below API 33, where silence is a refusal already given rather
    /// than a question still open.
    public let permissionIsAsked: Bool

    /// Whether the platform says an explanation would help.
    ///
    /// Always false on iOS, which offers no equivalent of Android's rationale
    /// flag — and needs none, because `.notDetermined` is answered directly.
    public let wouldExplain: Bool

    /// Whether this application has ever raised the prompt.
    ///
    /// Recorded by this plugin where the prompt is raised. iOS does not need it
    /// to reach the right answer; it is carried so that a disagreement between
    /// the platforms is a disagreement about the same four facts.
    public let everAsked: Bool

    /// The four facts a platform reports, in the order the rule reads them.
    public init(wouldAppear: Bool, permissionIsAsked: Bool, wouldExplain: Bool, everAsked: Bool) {
        self.wouldAppear = wouldAppear
        self.permissionIsAsked = permissionIsAsked
        self.wouldExplain = wouldExplain
        self.everAsked = everAsked
    }

    /// The three answers the application reasons in, as the wire spells them.
    public func said() -> String {
        if wouldAppear { return NotificationRule.granted }
        if !permissionIsAsked { return NotificationRule.denied }
        if wouldExplain { return NotificationRule.denied }
        if everAsked { return NotificationRule.denied }

        return NotificationRule.notDetermined
    }

    /// A notification posted now would appear.
    public static let granted = "granted"

    /// It would not, and nothing may ask again.
    public static let denied = "denied"

    /// Nobody has been asked; the point of first use is still ahead.
    public static let notDetermined = "not_determined"
}
