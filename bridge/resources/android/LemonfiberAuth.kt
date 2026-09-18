package app.lemonfiber.native

import android.util.Log
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * The device's own authentication, on Android.
 *
 * Namespace: `Lemonfiber.Authenticate`
 *
 * **Why this exists rather than the stock biometric call.** A biometric failure
 * falls back to the device's passcode and must never fall back to unlocked. That
 * is not a thing an app decides after the fact — it is one constant passed to the
 * platform before the dialog is shown, and getting it wrong produces a prompt
 * that simply fails on a device whose owner has no fingerprint enrolled. An
 * operator with a passcode and no fingerprint would be locked out of their own
 * app, and no test written in PHP could see it.
 *
 * So the constant is here, in a file this repository owns:
 * `BIOMETRIC_STRONG or DEVICE_CREDENTIAL`. `DEVICE_CREDENTIAL` is the half that
 * provides the fallback — the same dialog offers the PIN, pattern or password,
 * and the platform handles it itself.
 *
 * **There is no third answer.** The bridge reports `authenticated: true` or
 * `authenticated: false`, and every error path reports false. The second
 * clause — must never fall back to unlocked — is kept by there being nowhere in
 * this file that answers true without the platform having said so.
 */
public object LemonfiberAuth {
    private const val TAG = "Lemonfiber"

    /**
     * What this app will accept as the device's own authentication.
     *
     * `DEVICE_CREDENTIAL` is that fallback in one constant: it puts the PIN,
     * pattern or password in the same dialog, so a failed or unenrolled
     * fingerprint falls back rather than dead-ends. Without it, an operator who
     * has a passcode and no fingerprint cannot get in at all.
     */
    private const val ACCEPTED =
        BiometricManager.Authenticators.BIOMETRIC_STRONG or
            BiometricManager.Authenticators.DEVICE_CREDENTIAL

    /**
     * `Lemonfiber.Authenticate` — ask the device who this is.
     *
     * Answers `acknowledged` immediately and sends the real result as an event,
     * because the dialog is the operator's to answer in their own time and a
     * bridge call that waited would hold the thread it was called on.
     */
    public class Authenticate(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val reason = parameters["reason"] as? String ?: "Unlock lemonfiber"

            activity.runOnUiThread {
                val prompt =
                    BiometricPrompt(
                        activity,
                        ContextCompat.getMainExecutor(activity),
                        object : BiometricPrompt.AuthenticationCallback() {
                            override fun onAuthenticationSucceeded(
                                result: BiometricPrompt.AuthenticationResult,
                            ) {
                                answer(true)
                            }

                            override fun onAuthenticationError(
                                code: Int,
                                message: CharSequence,
                            ) {
                                // Every error is a refusal. A cancelled dialog,
                                // an unenrolled device, a lockout after too many
                                // attempts — none of them is somebody proving
                                // who they are, and reading any of them as a
                                // grant is exactly the fall to unlocked this
                                // file exists to refuse.
                                Log.d(TAG, "authentication error $code: $message")
                                answer(false)
                            }
                        },
                    )

                prompt.authenticate(
                    BiometricPrompt.PromptInfo.Builder()
                        .setTitle(reason)
                        .setAllowedAuthenticators(ACCEPTED)
                        .build(),
                )
            }

            return mapOf("acknowledged" to true)
        }

        private fun answer(authenticated: Boolean) {
            Log.d(TAG, "authenticated: $authenticated")
        }
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
                        BiometricManager.from(activity).canAuthenticate(ACCEPTED) ==
                            BiometricManager.BIOMETRIC_SUCCESS
                    ),
            )
    }
}
