/// Why the sheet was not put in front of anybody.
///
/// Two refusals and they are not the same sentence. *There was nothing to hand
/// over* is this application's own fault and is answered by assembling the
/// report again; *the platform would not* is the device's, and is answered by
/// trying again or by giving up on that road. A boolean cannot carry that, and a
/// screen behind one has to guess which it was.
///
/// Deliberately mirrors `HandoverRule.kt` line for line.
public enum WhyNothingWasHandedOver: String, Sendable {
    /// The file was not there to offer. Nothing was put in front of anybody.
    case nothingToHandOver = "nothing_to_hand_over"

    /// It was there and the sheet could not be presented.
    case thePlatformWouldNot = "the_platform_would_not"

    /// What this answer is called on the wire.
    public var word: String { rawValue }
}

/// Whether the sheet can be offered, and why not where it cannot.
///
/// **There is deliberately no answer for what the operator chose.** A handover
/// ends with them picking an app, with them dismissing the sheet, or with the
/// platform never presenting it. The first two are one answer here: where the
/// report went is none of this application's business, and an app that watched
/// where it went would not be honouring *assembled for the operator to send,
/// not sent*. Only the third is its own, because a sheet that never appeared
/// leaves somebody looking at a screen that did nothing.
///
/// No Apple framework in sight. Everything is a reading of two facts a caller
/// passes in, which is what lets it be run in SwiftPM in one second rather than
/// demonstrated on a handset.
///
/// Deliberately mirrors `HandoverRule.kt` line for line.
public struct HandoverRule: Equatable, Sendable {
    /// Whether there is a file at the path the caller named.
    public let thereIsSomethingToHandOver: Bool

    /// Whether the platform presented the sheet when it was asked to.
    public let theSheetWasPresented: Bool

    /// Built from what the platform managed, one fact at a time.
    public init(thereIsSomethingToHandOver: Bool, theSheetWasPresented: Bool) {
        self.thereIsSomethingToHandOver = thereIsSomethingToHandOver
        self.theSheetWasPresented = theSheetWasPresented
    }

    /// Whether the operator was shown the sheet at all.
    public var wasOffered: Bool {
        thereIsSomethingToHandOver && theSheetWasPresented
    }

    /// Why they were not, or nothing because they were.
    ///
    /// Read in this order and the order is load-bearing: a missing file is not a
    /// platform that would not present, and reporting it as one sends somebody
    /// to try again at a thing that will fail the same way every time.
    public var whyNot: WhyNothingWasHandedOver? {
        if !thereIsSomethingToHandOver {
            return .nothingToHandOver
        }

        return theSheetWasPresented ? nil : .thePlatformWouldNot
    }

    /// A report that is there and a sheet that opened: the ordinary handover.
    public static let offered = HandoverRule(
        thereIsSomethingToHandOver: true, theSheetWasPresented: true)
}
