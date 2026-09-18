package app.lemonfiber.native

/**
 * Why the camera came back without a pairing code.
 *
 * Three ways to get nothing and three different things for the operator to do
 * about it, closed on both sides of the wire so that a wrong word is a compile
 * error rather than a sentence nobody reads. A boundary carrying these as free
 * strings puts a default arm behind a spelling: a misspelt refusal falls
 * through to *the operator pressed back*, and that screen offers another go
 * forever without ever mentioning that the code can be typed instead.
 *
 * Deliberately mirrors `CameraRule.swift` line for line.
 */
public enum class WhyNothingWasRead(
    /** What this answer is called on the wire. */
    public val word: String,
) {
    /** They backed out of the scanner. Not a failure, and the ordinary way out. */
    THE_OPERATOR_CLOSED_IT("the_operator_closed_it"),

    /** The platform will not let this application have the camera. */
    THE_CAMERA_IS_NOT_PERMITTED("the_camera_is_not_permitted"),

    /** There is no camera on this device at all. */
    THERE_IS_NO_CAMERA("there_is_no_camera"),
}

/**
 * Whether the camera may be opened, and whether anybody may still be asked.
 *
 * The same shape as [NotificationRule], asking the same question of a different
 * permission: what the operator has said is [WhatTheOperatorSaid]'s to
 * reconstruct, because two capabilities reading the platform's facts for
 * themselves is two capabilities disagreeing quietly about what *denied* means.
 * What is here is the part that belongs to reading a code — which facts are
 * gathered, and the one distinction a scanner needs that a notification does
 * not.
 *
 * **That distinction is [mayAskAgain].** A camera refused in the dialog a
 * moment ago and a camera refused in settings some time ago arrive at this
 * application identically, and they need opposite sentences: the first can be
 * put again at the point of first use, and the second cannot be put at all and
 * has to send the operator to Settings. A screen that cannot tell them apart
 * either nags somebody who has settled the question or sends somebody to a
 * settings page they never needed to see.
 *
 * No Android framework in sight. Everything is a reading of four booleans a
 * caller passes in, which is what lets it be run on a JVM in two seconds rather
 * than demonstrated on a handset.
 *
 * Deliberately mirrors `CameraRule.swift` line for line.
 */
public data class CameraRule(
    /**
     * Whether this device has a camera at all.
     *
     * Its own fact rather than folded into the permission, because no amount of
     * visiting Settings adds a lens. Sending somebody there is the advice that
     * wastes the most of their time, and it is the advice a rule that knew only
     * about permissions would always give.
     */
    public val cameraExists: Boolean,
    /**
     * Whether the camera permission is granted right now.
     *
     * The counterpart of a notification's *would it appear*: one fact standing
     * for whatever the operator last decided, however they decided it.
     */
    public val permissionIsGranted: Boolean,
    /**
     * Whether the platform says an explanation would help.
     *
     * True only in the window between a first refusal and a settled one, which
     * is what makes it evidence of a refusal that can still be revisited. Never
     * true before the first prompt and never true after a permanent one. Always
     * false on iOS, which shows the camera prompt exactly once in the life of an
     * install and has nothing of the kind.
     */
    public val wouldExplain: Boolean,
    /**
     * Whether this application has ever raised the camera prompt.
     *
     * Kept by this plugin because nothing else can keep it: Android reports
     * *never asked* and *refused for good* identically, and the difference is
     * the whole of whether a prompt or a trip to Settings is the useful advice.
     * Recorded where the prompt is raised rather than inferred afterwards.
     */
    public val everAsked: Boolean,
) {
    /**
     * What the operator has said, as far as anything can tell.
     *
     * The camera permission exists on every version of both platforms this
     * application runs on, so the *is there a runtime permission at all*
     * question a notification has to ask is answered `true` here and not
     * carried as a fact. It is passed rather than dropped because the
     * reconstruction is shared: a second copy taking three arguments instead of
     * four is the copy that drifts.
     */
    public val said: WhatTheOperatorSaid
        get() = WhatTheOperatorSaid.readFrom(permissionIsGranted, true, wouldExplain, everAsked)

    /** Whether the scanner may be opened now, without asking anybody anything. */
    public val mayOpen: Boolean
        get() = cameraExists && said.mayProceed

    /** Whether the prompt may be raised for the first time. */
    public val mayAsk: Boolean
        get() = cameraExists && said.mayAsk

    /**
     * Whether putting the prompt up could still change the answer.
     *
     * Deliberately wider than [mayAsk], which is only about a camera nobody has
     * been asked about yet. This also covers the one refusal Android will
     * reconsider — the operator declined the dialog once, and the platform is
     * still willing to show it. On iOS this is never true after a refusal,
     * because the system shows that prompt once and then silently does nothing,
     * and the only remaining road is Settings.
     *
     * A screen chooses its sentence from this: *try again* where it is true,
     * *open Settings* where it is false, and the typed road either way.
     */
    public val mayAskAgain: Boolean
        get() = cameraExists && (said.mayAsk || wouldExplain)

    /**
     * Why nothing can be read, or nothing because something can.
     *
     * Null while [mayAsk] is true, and that is the case worth stating: a camera
     * nobody has been asked about is not a refusal, it is a question that has
     * not been put yet. A shim reading it as one would offer the typed road to
     * somebody who has never seen the prompt, which is the first-use permission
     * this application owes turned into a permission it never requests.
     *
     * [WhyNothingWasRead.THE_OPERATOR_CLOSED_IT] is never answered here. It is
     * not a fact about a permission — it is what happened while the scanner was
     * open, which only the shim that opened it can know.
     */
    public val whyNot: WhyNothingWasRead?
        get() =
            when {
                !cameraExists -> WhyNothingWasRead.THERE_IS_NO_CAMERA
                mayOpen || mayAsk -> null
                else -> WhyNothingWasRead.THE_CAMERA_IS_NOT_PERMITTED
            }

    /** Where the starting state lives, named so it reads at a call site. */
    public companion object {
        /**
         * A device with a camera that nobody has been asked about.
         *
         * What a first launch looks like, and the state the other cases are
         * written as a departure from.
         */
        public val UNASKED: CameraRule =
            CameraRule(
                cameraExists = true,
                permissionIsGranted = false,
                wouldExplain = false,
                everAsked = false,
            )
    }
}
