package app.lemonfiber.native

import android.app.Activity
import android.app.Application
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.os.SystemClock
import android.util.Log
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import androidx.lifecycle.DefaultLifecycleObserver
import androidx.lifecycle.LifecycleOwner
import androidx.lifecycle.ProcessLifecycleOwner
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.ui.nativerender.NativeElementBridge
import java.lang.ref.WeakReference
import java.util.concurrent.ArrayBlockingQueue
import java.util.concurrent.TimeUnit

/**
 * The app lock, on Android.
 *
 * Namespace: `Lemonfiber.Lock.*`, and `Lemonfiber.Authenticate` through
 * [LemonfiberAuth].
 *
 * What the lock is decided by lives in [LockRule], which imports nothing from
 * the framework and is tested on a JVM. This file holds the one rule the
 * process has, feeds it the lifecycle and the device's answers, and puts the
 * cover over the window when the rule says the glass must show nothing.
 *
 * **Only the platform's success callback opens the lock.** PHP reads whether
 * the lock stands through [Standing], a bridge answer, and is woken by an
 * event that carries nothing: an event claiming the lock opened would be an
 * event anything able to send one could forge.
 *
 * **Leaving is the whole app leaving**, so the scanner and a rotation are not
 * leaving and a share sheet in another app is.
 */
public object TheLock {
    private const val TAG = "Lemonfiber"

    /** The event that wakes PHP to read the lock again. It carries nothing. */
    private const val MOVED = "Lemonfiber\\Native\\Events\\TheLockMoved"

    /** Milliseconds in a second, for the monotonic clock. */
    private const val MILLIS = 1000L

    /** How long a call waits for the operator to answer the prompt. */
    private const val PATIENCE_MINUTES = 5L

    /** Every read and write of [rule] holds this. */
    private val guard = Any()

    /** The lock, for the life of the process. A new process is a cold start. */
    private var rule: LockRule = LockRule.coldStart()

    /** The activity in front, or the last one that was. Main thread only. */
    private var front: WeakReference<Activity>? = null

    /**
     * The monotonic clock, in seconds.
     *
     * `elapsedRealtime` counts deep sleep and cannot be set by the operator,
     * so neither a sleeping phone nor a clock set back shortens a time away.
     */
    private fun now(): Long = SystemClock.elapsedRealtime() / MILLIS

    /** Whether the device has a screen lock to ask with. */
    private fun canAsk(activity: Activity): Boolean =
        BiometricManager.from(activity).canAuthenticate(WhatUnlocks.ACCEPTED) ==
            BiometricManager.BIOMETRIC_SUCCESS

    /** Change the rule, and answer what it was before and what it is now. */
    private fun change(how: (LockRule) -> LockRule): Pair<LockRule, LockRule> =
        synchronized(guard) {
            val was = rule
            rule = how(rule)
            was to rule
        }

    /** Whether the lock stands right now. */
    internal fun stands(activity: Activity): Boolean =
        synchronized(guard) { rule }.standsAt(now(), canAsk(activity))

    /**
     * Start watching the app come and go.
     *
     * Leaving is the process going to the background, as `ProcessLifecycleOwner`
     * reports it: every activity of this app stopped, and not for a rotation.
     * The scanner is an activity of this app, so reading a code is not leaving;
     * a share sheet belongs to another app, so it is. Added on the main thread,
     * where the owner is read, and it catches up with a start that came first.
     */
    internal fun install(application: Application) {
        application.registerActivityLifecycleCallbacks(
            object : Application.ActivityLifecycleCallbacks {
                override fun onActivityStarted(activity: Activity) {
                    front = WeakReference(activity)
                }

                override fun onActivityResumed(activity: Activity) {
                    front = WeakReference(activity)
                }

                override fun onActivityStopped(activity: Activity) {
                    if (front?.get() == null) {
                        front = WeakReference(activity)
                    }
                }

                override fun onActivityCreated(
                    activity: Activity,
                    state: Bundle?,
                ) = Unit

                override fun onActivityPaused(activity: Activity) = Unit

                override fun onActivitySaveInstanceState(
                    activity: Activity,
                    out: Bundle,
                ) = Unit

                override fun onActivityDestroyed(activity: Activity) = Unit
            },
        )

        Handler(Looper.getMainLooper()).post {
            ProcessLifecycleOwner.get().lifecycle.addObserver(
                object : DefaultLifecycleObserver {
                    /** The app came back. */
                    override fun onStart(owner: LifecycleOwner) {
                        val activity = front?.get() ?: return
                        val (was, became) = change { it.returned(now(), canAsk(activity)) }

                        show(activity)

                        if (became.held && !was.held) {
                            wake()
                        }
                    }

                    /** The app left. */
                    override fun onStop(owner: LifecycleOwner) {
                        change { it.left(now()) }
                        front?.get()?.let { show(it) }
                    }
                },
            )
        }
    }

    /** Tell PHP to read the lock again. */
    private fun wake() {
        NativeElementBridge.sendNativeEvent(MOVED, "{}")
    }

    /** Put the cover up or take it down, as the rule says. */
    private fun show(activity: Activity) {
        TheCover.show(activity, synchronized(guard) { rule }.mustCover)
    }

    /**
     * Raise the device's own prompt, and hand its answer to [answer].
     *
     * Refused, as a failure, where a prompt is already up — two prompts would be
     * two answers to one question — and, [byItself], where the rule does not
     * allow asking unasked. [WhatUnlocks.ACCEPTED] puts the passcode in
     * the same dialog, so a biometric failure falls back to it.
     *
     * `onAuthenticationFailed` is one fingerprint that did not match while the
     * dialog stays up, not an answer, so it is not listened for.
     */
    internal fun prompt(
        activity: FragmentActivity,
        reason: String,
        byItself: Boolean,
        answer: (Boolean) -> Unit,
    ) {
        val (was, became) =
            change {
                when {
                    it.prompting -> it
                    byItself && !it.mayAskByItself -> it
                    byItself -> it.askingByItself()
                    else -> it.asking()
                }
            }

        if (was.prompting || !became.prompting) {
            answer(false)

            return
        }

        activity.runOnUiThread {
            val finish = { succeeded: Boolean ->
                change { it.answered(succeeded) }
                show(activity)
                Log.d(TAG, "authenticated: $succeeded")
                answer(succeeded)
            }

            BiometricPrompt(
                activity,
                ContextCompat.getMainExecutor(activity),
                object : BiometricPrompt.AuthenticationCallback() {
                    override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                        finish(true)
                    }

                    override fun onAuthenticationError(
                        code: Int,
                        message: CharSequence,
                    ) {
                        // A cancel, a lockout, no enrolment: none of them is
                        // somebody proving who they are.
                        Log.d(TAG, "authentication error $code")
                        finish(false)
                    }
                },
            ).authenticate(
                BiometricPrompt.PromptInfo.Builder()
                    .setTitle(reason)
                    .setAllowedAuthenticators(WhatUnlocks.ACCEPTED)
                    .build(),
            )
        }
    }

    /**
     * Raise the prompt and wait for its answer on the calling thread.
     *
     * Never on the main thread, whose dialog it would be waiting for; a call
     * arriving there answers no. The wait is bounded, and a prompt left up past
     * it answers no here while the lock stays held.
     */
    internal fun promptAndWait(
        activity: FragmentActivity,
        reason: String,
    ): Boolean {
        if (Looper.myLooper() == Looper.getMainLooper()) {
            return false
        }

        val answered = ArrayBlockingQueue<Boolean>(1)

        prompt(activity, reason, byItself = false) { answered.offer(it) }

        return answered.poll(PATIENCE_MINUTES, TimeUnit.MINUTES) ?: false
    }

    /** `Lemonfiber.Lock.Standing` — whether the lock stands right now. */
    public class Standing(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            mapOf("open" to !stands(activity))
    }

    /**
     * `Lemonfiber.Lock.Waive` — the store holds nothing for the lock to guard.
     *
     * Asked by PHP only after reading the store and finding it empty.
     */
    public class Waive(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            change { it.waived() }
            show(activity)

            return mapOf("open" to !stands(activity))
        }
    }

    /**
     * `Lemonfiber.Lock.Drawn` — the lock screen is on the glass.
     *
     * The cover comes down two frames later, once the frame PHP published has
     * been drawn under it. Where `ask` is true and the rule allows it, the
     * prompt goes up by itself.
     */
    public class Drawn(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            change { it.drawn() }

            activity.runOnUiThread {
                activity.window.decorView.postOnAnimation {
                    activity.window.decorView.postOnAnimation { show(activity) }
                }
            }

            val reason = parameters["reason"] as? String

            if (parameters["ask"] == true && reason != null) {
                prompt(activity, reason, byItself = true) { succeeded -> if (succeeded) wake() }
            }

            return mapOf("open" to !stands(activity))
        }
    }
}
