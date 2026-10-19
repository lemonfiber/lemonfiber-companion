package app.lemonfiber.native

/**
 * Where this phone may keep the key that proves its operator to a stack, and the words
 * for what became of one.
 *
 * The decisions [PhoneKeyFunctions] turns on, written apart from it so a JVM with no
 * device in sight can hold them: which of the platform's answers is a key in hardware,
 * which is a key in software the stack would refuse, and which failed prompt is somebody
 * declining rather than something going wrong. The constants are the framework's, written
 * out here so this file imports nothing from it.
 *
 * Deliberately mirrors `PhoneKeyRule.swift` word for word.
 */
public object PhoneKeyRule {
    /** `KeyProperties.SECURITY_LEVEL_SOFTWARE`. */
    public const val SECURITY_LEVEL_SOFTWARE: Int = 0

    /** `KeyProperties.SECURITY_LEVEL_TRUSTED_ENVIRONMENT`. */
    public const val SECURITY_LEVEL_TRUSTED_ENVIRONMENT: Int = 1

    /** `KeyProperties.SECURITY_LEVEL_STRONGBOX`. */
    public const val SECURITY_LEVEL_STRONGBOX: Int = 2

    /** `KeyProperties.SECURITY_LEVEL_UNKNOWN_SECURE`: in secure hardware, the platform cannot say which. */
    public const val SECURITY_LEVEL_UNKNOWN_SECURE: Int = -1

    /** `BiometricManager.BIOMETRIC_SUCCESS`. */
    public const val BIOMETRIC_SUCCESS: Int = 0

    /** `BiometricManager.BIOMETRIC_ERROR_NONE_ENROLLED`. */
    public const val BIOMETRIC_ERROR_NONE_ENROLLED: Int = 11

    /** `BiometricPrompt.ERROR_CANCELED`: the system took the prompt down. */
    public const val ERROR_CANCELED: Int = 5

    /** `BiometricPrompt.ERROR_USER_CANCELED`. */
    public const val ERROR_USER_CANCELED: Int = 10

    /** `BiometricPrompt.ERROR_NEGATIVE_BUTTON`: the operator tapped Cancel. */
    public const val ERROR_NEGATIVE_BUTTON: Int = 13

    /**
     * Where a key may live on a phone that answers these, or why it may not live anywhere.
     *
     * A face or fingerprint is required before anything else, because a key no strong
     * biometric guards is a key this app does not make: the passcode is not a way past it.
     */
    public fun mayLive(
        biometric: Int,
        strongBox: Boolean,
    ): Either =
        when {
            biometric == BIOMETRIC_ERROR_NONE_ENROLLED -> Either.Not(WhyNoPhoneKey.NO_BIOMETRIC)
            biometric != BIOMETRIC_SUCCESS -> Either.Not(WhyNoPhoneKey.NO_HARDWARE)
            strongBox -> Either.Lives(WhereAPhoneKeyLives.STRONGBOX)
            else -> Either.Lives(WhereAPhoneKeyLives.TEE)
        }

    /**
     * Where a key that was made lives, by the security level the platform reports for it,
     * or nothing where it lives in software and so has to go.
     *
     * A level the platform cannot name but calls secure is the trusted environment's: it is
     * not the chip, and the stack reads the attestation for the truth either way.
     */
    public fun madeIn(securityLevel: Int): WhereAPhoneKeyLives? =
        when (securityLevel) {
            SECURITY_LEVEL_STRONGBOX -> WhereAPhoneKeyLives.STRONGBOX
            SECURITY_LEVEL_TRUSTED_ENVIRONMENT, SECURITY_LEVEL_UNKNOWN_SECURE -> WhereAPhoneKeyLives.TEE
            else -> null
        }

    /** Why a prompt answered with this error code signed nothing: somebody declining, or a failure. */
    public fun whyNotSigned(errorCode: Int): WhyNothingWasSigned =
        when (errorCode) {
            ERROR_CANCELED, ERROR_USER_CANCELED, ERROR_NEGATIVE_BUTTON -> WhyNothingWasSigned.CANCELLED
            else -> WhyNothingWasSigned.FAILED
        }

    /** The certificates an attestation needs at least: the key's own, and one vouching for it. */
    public const val ATTESTED_CHAIN: Int = 2

    /**
     * Whether a chain of this many certificates attests anything.
     *
     * A chain of one is the key's own certificate signed by itself, which is what the
     * Keystore hands back where it attests nothing.
     */
    public fun attested(certificates: Int): Boolean = certificates >= ATTESTED_CHAIN

    /**
     * What deleting a key came to, by whether it was there before and is there after.
     *
     * A key still there is never said to be gone, whatever the deletion answered.
     */
    public fun thrownAway(
        heldBefore: Boolean,
        heldAfter: Boolean,
    ): ThrownAway =
        when {
            heldAfter -> ThrownAway.NOT_FORGOTTEN
            heldBefore -> ThrownAway.FORGOTTEN
            else -> ThrownAway.NONE_HELD
        }

    /** Where a key may live, or why it may not. */
    public sealed interface Either {
        /** It may live there. */
        public data class Lives(
            /** Where. */
            val where: WhereAPhoneKeyLives,
        ) : Either

        /** It may not live anywhere on this phone, for this reason. */
        public data class Not(
            /** Why not. */
            val why: WhyNoPhoneKey,
        ) : Either
    }
}

/** Where a phone key lives, in the words the stack records it under. */
public enum class WhereAPhoneKeyLives(
    /** What it is called on the wire. */
    public val word: String,
) {
    /** A separate secure chip. */
    STRONGBOX("strongbox"),

    /** The processor's trusted environment. */
    TEE("tee"),
}

/** Why no phone key could be made, each answered differently on the screen. */
public enum class WhyNoPhoneKey(
    /** What it is called on the wire. */
    public val word: String,
) {
    /** The phone has nowhere in hardware to keep one. */
    NO_HARDWARE("no_hardware"),

    /** No face or fingerprint is set up, so nothing could guard one. */
    NO_BIOMETRIC("no_biometric"),

    /** The phone made it in software, so it was thrown away. */
    IN_SOFTWARE("in_software"),

    /** The phone would not attest where it made it. */
    UNATTESTED("unattested"),

    /** Something else went wrong; trying again is reasonable. */
    FAILED("failed"),
}

/** What throwing a key away came to. */
public enum class ThrownAway(
    /** What it is called on the wire. */
    public val word: String,
) {
    /** It was there, and it is gone. */
    FORGOTTEN("forgotten"),

    /** There was none to throw away. */
    NONE_HELD("none_held"),

    /** It could not be thrown away, so it may still be there. */
    NOT_FORGOTTEN("not_forgotten"),
}

/** Why nothing was signed. */
public enum class WhyNothingWasSigned(
    /** What it is called on the wire. */
    public val word: String,
) {
    /** The operator declined the prompt. */
    CANCELLED("cancelled"),

    /** The key is gone, or a new face or fingerprint made it unusable, so it was thrown away. */
    GONE("gone"),

    /** Something else went wrong; trying again is reasonable. */
    FAILED("failed"),
}
