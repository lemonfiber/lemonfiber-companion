package app.lemonfiber.native

import androidx.biometric.BiometricManager
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * The device's own authentication, on Android.
 *
 * Namespace: `Lemonfiber.Authenticate`, `Lemonfiber.CanAuthenticate`
 *
 * **Why this exists rather than the stock biometric call.** A biometric failure
 * falls back to the device's passcode and must never fall back to unlocked.
 * That is [WhatUnlocks.ACCEPTED], chosen before the dialog is shown: without
 * `DEVICE_CREDENTIAL` an operator with a passcode and no fingerprint, or whose
 * fingerprints were removed, could not get in at all.
 *
 * **The answer is the platform's.** `Authenticate` waits for the dialog and
 * answers `authenticated: true` only from the success callback, which is also
 * the one place [TheLock] opens.
 */
public object LemonfiberAuth {
    /** What the prompt is titled with where a caller sends no reason. */
    private const val UNLOCK = "Unlock lemonfiber"

    /**
     * `Lemonfiber.Authenticate` — ask the device who this is, and wait for it.
     *
     * The bridge thread waits while the operator answers, as it does for the
     * camera and the notification prompt, so what comes back is what they did.
     */
    public class Authenticate(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            mapOf(
                "authenticated" to TheLock.promptAndWait(activity, parameters["reason"] as? String ?: UNLOCK),
            )
    }

    /**
     * `Lemonfiber.CanAuthenticate` — whether the device can ask at all.
     *
     * A device with no screen lock configured cannot authenticate anybody, and
     * that is a different condition from somebody declining — one is answered by
     * telling the operator to set a screen lock, the other by asking again.
     */
    public class CanAuthenticate(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            mapOf(
                "canAuthenticate" to
                    (
                        BiometricManager.from(activity).canAuthenticate(WhatUnlocks.ACCEPTED) ==
                            BiometricManager.BIOMETRIC_SUCCESS
                    ),
            )
    }
}
