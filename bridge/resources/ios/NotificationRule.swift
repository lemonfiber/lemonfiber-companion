import Foundation

/// Whether a notification would be seen, and whether anybody may still be asked.
///
/// The four facts a platform can report about notifications, and what they mean
/// together. Reconstructing the three answers out of them is
/// `WhatTheOperatorSaid`'s, because the camera asks the identical question and
/// two copies of that decision would be two capabilities disagreeing quietly.
/// What is here is the part that belongs to telling somebody: which facts are
/// gathered, and what the answer is called once the subject is a notification.
///
/// No UserNotifications, no UIKit. Everything is a reading of four booleans a
/// caller passes in, which is what lets it be run on a laptop rather than
/// demonstrated on a handset.
///
/// Deliberately mirrors `NotificationRule.kt` line for line.
public struct NotificationRule: Equatable, Sendable {
    /// Whether a notification posted right now would be seen.
    ///
    /// One fact covering every switch that could stop it, because the operator
    /// turning any of them off means the same thing to a caller with something
    /// to show.
    public let wouldAppear: Bool

    /// Whether this platform has a runtime permission to ask for at all.
    ///
    /// Always true here. It is false below Android 33, where notifications are
    /// granted at install time and turned off in settings afterwards, and it is
    /// carried on both sides so that both answer the same question.
    public let permissionIsAsked: Bool

    /// Whether the platform says an explanation would help.
    ///
    /// Always false here; iOS has nothing of the kind. On Android it is true
    /// only after a refusal and never before the first prompt or after a
    /// permanent one, so it identifies exactly one of the three states and
    /// cannot stand alone.
    public let wouldExplain: Bool

    /// Whether this application has ever raised the prompt.
    ///
    /// Kept by this plugin because nothing else can keep it on Android: there
    /// is no call that answers it, and the two states it separates — never
    /// asked, and refused so firmly the platform stopped offering — are
    /// identical from the outside. Recorded here too, so that the two platforms
    /// answer from the same kind of record rather than one of them answering
    /// from a better one.
    public let everAsked: Bool

    /// - Parameters:
    ///   - wouldAppear: whether a notification posted now would be seen.
    ///   - permissionIsAsked: whether this platform has a runtime permission.
    ///   - wouldExplain: whether the platform says an explanation would help.
    ///   - everAsked: whether this application has ever raised the prompt.
    public init(
        wouldAppear: Bool,
        permissionIsAsked: Bool,
        wouldExplain: Bool,
        everAsked: Bool
    ) {
        self.wouldAppear = wouldAppear
        self.permissionIsAsked = permissionIsAsked
        self.wouldExplain = wouldExplain
        self.everAsked = everAsked
    }

    /// What the operator has said, as far as anything can tell.
    public var said: WhatTheOperatorSaid {
        WhatTheOperatorSaid.readFrom(
            wouldAppear: wouldAppear,
            permissionIsAsked: permissionIsAsked,
            wouldExplain: wouldExplain,
            everAsked: everAsked
        )
    }

    /// Whether the prompt may be raised now.
    ///
    /// Named here as well as on the answer because the shim asks this question
    /// of the rule rather than of the word: what a caller has in hand is the
    /// four facts, and the two readings of them belong side by side.
    public var mayAsk: Bool { said.mayAsk }

    /// Whether something posted now would reach the operator.
    public var mayShow: Bool { said.mayProceed }

    /// A device nobody has asked anything, on a platform that asks.
    ///
    /// What a first launch looks like, and the state the other cases are
    /// written as a departure from.
    public static let unasked = NotificationRule(
        wouldAppear: false,
        permissionIsAsked: true,
        wouldExplain: false,
        everAsked: false
    )
}
