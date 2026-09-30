package app.lemonfiber.native

/**
 * What the device's own prompt accepts as proof of who is holding the phone.
 *
 * The values are `BiometricManager.Authenticators`', written here so that a
 * test on a JVM can read them: this file imports nothing from the framework.
 *
 * [DEVICE_CREDENTIAL] is what puts the PIN, pattern or password in the same
 * dialog as the fingerprint, so a failed, missing or removed fingerprint falls
 * back to the passcode rather than to nothing, and rather than to unlocked.
 */
public object WhatUnlocks {
    /** `BiometricManager.Authenticators.BIOMETRIC_STRONG`. */
    public const val BIOMETRIC_STRONG: Int = 0x000F

    /** `BiometricManager.Authenticators.DEVICE_CREDENTIAL`. */
    public const val DEVICE_CREDENTIAL: Int = 0x8000

    /** Every authenticator the prompt is allowed to offer. */
    public const val ACCEPTED: Int = BIOMETRIC_STRONG or DEVICE_CREDENTIAL
}
