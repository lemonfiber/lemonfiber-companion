import Foundation

/// Why the camera came back without a pairing code.
///
/// Three ways to get nothing and three different things for the operator to do
/// about it, closed on both sides of the wire so that a wrong word is a compile
/// error rather than a sentence nobody reads. A boundary carrying these as free
/// strings puts a default arm behind a spelling: a misspelt refusal falls
/// through to *the operator pressed back*, and that screen offers another go
/// forever without ever mentioning that the code can be typed instead.
///
/// Deliberately mirrors `CameraRule.kt` line for line.
public enum WhyNothingWasRead: String, Sendable {
    /// They backed out of the scanner. Not a failure, and the ordinary way out.
    case theOperatorClosedIt = "the_operator_closed_it"

    /// The platform will not let this application have the camera.
    case theCameraIsNotPermitted = "the_camera_is_not_permitted"

    /// There is no camera on this device at all.
    case thereIsNoCamera = "there_is_no_camera"

    /// What this answer is called on the wire.
    public var word: String { rawValue }
}

/// Whether the camera may be opened, and whether anybody may still be asked.
///
/// The same shape as `NotificationRule`, asking the same question of a
/// different permission: what the operator has said is `WhatTheOperatorSaid`'s
/// to reconstruct, because two capabilities reading the platform's facts for
/// themselves is two capabilities disagreeing quietly about what *denied*
/// means. What is here is the part that belongs to reading a code — which facts
/// are gathered, and the one distinction a scanner needs that a notification
/// does not.
///
/// **That distinction is `mayAskAgain`.** A camera refused in the dialog a
/// moment ago and a camera refused in settings some time ago arrive at this
/// application identically, and they need opposite sentences: the first can be
/// put again at the point of first use, and the second cannot be put at all and
/// has to send the operator to Settings. A screen that cannot tell them apart
/// either nags somebody who has settled the question or sends somebody to a
/// settings page they never needed to see.
///
/// No AVFoundation in sight. Everything is a reading of four booleans a caller
/// passes in, which is what lets it be run on a laptop rather than demonstrated
/// on a handset.
///
/// Deliberately mirrors `CameraRule.kt` line for line.
public struct CameraRule: Equatable, Sendable {
    /// Whether this device has a camera at all.
    ///
    /// Its own fact rather than folded into the permission, because no amount
    /// of visiting Settings adds a lens. Sending somebody there is the advice
    /// that wastes the most of their time, and it is the advice a rule that
    /// knew only about permissions would always give.
    public let cameraExists: Bool

    /// Whether the camera permission is granted right now.
    ///
    /// The counterpart of a notification's *would it appear*: one fact standing
    /// for whatever the operator last decided, however they decided it.
    public let permissionIsGranted: Bool

    /// Whether the platform says an explanation would help.
    ///
    /// True only in the window between a first refusal and a settled one, which
    /// is what makes it evidence of a refusal that can still be revisited.
    /// Never true before the first prompt and never true after a permanent one.
    /// Always false here, because iOS shows the camera prompt exactly once in
    /// the life of an install and has nothing of the kind — carried anyway so
    /// that the two halves answer the same questions.
    public let wouldExplain: Bool

    /// Whether this application has ever raised the camera prompt.
    ///
    /// Kept by this plugin because nothing else can keep it on the other
    /// platform, where *never asked* and *refused for good* are reported
    /// identically. iOS answers `.notDetermined` directly and needs no such
    /// record; it is taken anyway, for the reason `wouldExplain` is.
    public let everAsked: Bool

    /// - Parameters:
    ///   - cameraExists: whether this device has a camera at all.
    ///   - permissionIsGranted: whether the camera permission is granted now.
    ///   - wouldExplain: whether the platform says an explanation would help.
    ///   - everAsked: whether this application has ever raised the prompt.
    public init(
        cameraExists: Bool,
        permissionIsGranted: Bool,
        wouldExplain: Bool,
        everAsked: Bool
    ) {
        self.cameraExists = cameraExists
        self.permissionIsGranted = permissionIsGranted
        self.wouldExplain = wouldExplain
        self.everAsked = everAsked
    }

    /// What the operator has said, as far as anything can tell.
    ///
    /// The camera permission exists on every version of both platforms this
    /// application runs on, so the *is there a runtime permission at all*
    /// question a notification has to ask is answered `true` here and not
    /// carried as a fact. It is passed rather than dropped because the
    /// reconstruction is shared: a second copy taking three arguments instead
    /// of four is the copy that drifts.
    public var said: WhatTheOperatorSaid {
        WhatTheOperatorSaid.readFrom(
            wouldAppear: permissionIsGranted,
            permissionIsAsked: true,
            wouldExplain: wouldExplain,
            everAsked: everAsked
        )
    }

    /// Whether the scanner may be opened now, without asking anybody anything.
    public var mayOpen: Bool {
        cameraExists && said.mayProceed
    }

    /// Whether the prompt may be raised for the first time.
    public var mayAsk: Bool {
        cameraExists && said.mayAsk
    }

    /// Whether putting the prompt up could still change the answer.
    ///
    /// Deliberately wider than `mayAsk`, which is only about a camera nobody
    /// has been asked about yet. This also covers the one refusal the other
    /// platform will reconsider — the operator declined the dialog once, and it
    /// is still willing to show it. Here this is never true after a refusal,
    /// because the system shows that prompt once and then silently does
    /// nothing, and the only remaining road is Settings.
    ///
    /// A screen chooses its sentence from this: *try again* where it is true,
    /// *open Settings* where it is false, and the typed road either way.
    public var mayAskAgain: Bool {
        cameraExists && (said.mayAsk || wouldExplain)
    }

    /// Why nothing can be read, or nothing because something can.
    ///
    /// Nil while `mayAsk` is true, and that is the case worth stating: a camera
    /// nobody has been asked about is not a refusal, it is a question that has
    /// not been put yet. A shim reading it as one would offer the typed road to
    /// somebody who has never seen the prompt, which is the first-use
    /// permission this application owes turned into one it never requests.
    ///
    /// `WhyNothingWasRead.theOperatorClosedIt` is never answered here. It is
    /// not a fact about a permission — it is what happened while the scanner
    /// was open, which only the shim that opened it can know.
    public var whyNot: WhyNothingWasRead? {
        if !cameraExists {
            return .thereIsNoCamera
        }

        return mayOpen || mayAsk ? nil : .theCameraIsNotPermitted
    }

    /// A device with a camera that nobody has been asked about.
    ///
    /// What a first launch looks like, and the state the other cases are
    /// written as a departure from.
    public static let unasked = CameraRule(
        cameraExists: true,
        permissionIsGranted: false,
        wouldExplain: false,
        everAsked: false
    )
}
