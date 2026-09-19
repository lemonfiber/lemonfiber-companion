package app.lemonfiber.native

import android.app.Activity
import android.app.Application
import android.os.Bundle
import android.util.Log
import android.view.WindowManager
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * The Android half of lemonfiber's own native expansion.
 *
 * Namespace: `Lemonfiber.*`
 *
 * Everything here is a thin shim over one window flag. The decision about *when*
 * that flag belongs on lives in [CaptureRule], which imports nothing from the
 * framework and is unit-tested; this file is what cannot be tested off a device
 * and is deliberately kept down to the calls that need one.
 *
 * `FLAG_SECURE` is Android's whole answer to both requirements. One flag blanks
 * the recents-screen thumbnail and refuses screenshots and screen recording
 * together. There is no finer-grained control: a view cannot opt out of a
 * capture on its own, so the flag goes on the window or the protection does not
 * exist.
 *
 * **It is applied on `onPause`, not held forever.** Holding it permanently would
 * be simpler and is what most examples do, and it would also refuse every
 * deliberate screenshot for the life of the app — including the one an operator
 * takes to send to somebody who can help. The recents snapshot is taken after
 * `onPause`, so setting it there is in time for the only capture the
 * task-switcher rule is about. A guarded screen sets it in the foreground too,
 * and keeps it.
 */
public object LemonfiberFunctions {
    private const val TAG = "Lemonfiber"

    /**
     * What the app is showing and whether anybody is looking at it.
     *
     * Process-wide because the window is. A companion holds one activity, and
     * two screens cannot disagree about whether the window is protected — so
     * this is one value rather than a per-screen flag that the last writer wins.
     */
    @Volatile
    private var rule: CaptureRule = CaptureRule.LAUNCHED

    /**
     * Start watching the app move in and out of the foreground.
     *
     * Called once from `installLemonfiber`, which is the symbol the builder
     * calls. Without it the window is never protected, because nothing would
     * notice the app leaving — and the failure would be invisible: every screen
     * would look right, and the task switcher would quietly hold the last frame.
     */
    @JvmStatic
    public fun install(application: Application) {
        application.registerActivityLifecycleCallbacks(
            object : Application.ActivityLifecycleCallbacks {
                override fun onActivityPaused(activity: Activity) {
                    rule = rule.backgrounded()
                    apply(activity)
                }

                override fun onActivityResumed(activity: Activity) {
                    rule = rule.foregrounded()
                    apply(activity)
                }

                override fun onActivityCreated(
                    activity: Activity,
                    state: Bundle?,
                ) = Unit

                override fun onActivityStarted(activity: Activity) = Unit

                override fun onActivityStopped(activity: Activity) = Unit

                override fun onActivitySaveInstanceState(
                    activity: Activity,
                    out: Bundle,
                ) = Unit

                override fun onActivityDestroyed(activity: Activity) = Unit
            },
        )
    }

    /**
     * Put the window into whatever state the rule now calls for.
     *
     * The flag is set and cleared rather than toggled, so a state that is
     * already correct costs nothing and a state that drifted is corrected. A
     * toggle would have to be right about what it was before.
     */
    private fun apply(activity: Activity) {
        activity.runOnUiThread {
            if (rule.mustProtect) {
                activity.window.setFlags(
                    WindowManager.LayoutParams.FLAG_SECURE,
                    WindowManager.LayoutParams.FLAG_SECURE,
                )
            } else {
                activity.window.clearFlags(WindowManager.LayoutParams.FLAG_SECURE)
            }

            Log.d(TAG, "capture protection: ${rule.mustProtect} (rule=$rule)")
        }
    }

    /** `Lemonfiber.Conceal` — a screen holding a secret has come up. */
    public class Conceal(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            rule = rule.concealing()
            apply(activity)

            return mapOf("protected" to rule.mustProtect)
        }
    }

    /** `Lemonfiber.Reveal` — that screen has gone. */
    public class Reveal(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            rule = rule.revealing()
            apply(activity)

            return mapOf("protected" to rule.mustProtect)
        }
    }

    /**
     * `Lemonfiber.IsProtected` — whether the window is protected right now.
     *
     * Answered from the rule rather than by reading the window's flags back.
     * Reading them back would be the more suspicious-sounding choice and is the
     * wrong one: `getAttributes().flags` is whatever was last set on the UI
     * thread, and this call arrives from the bridge thread, so a read can beat
     * its own write and report the previous state as the current one.
     */
    public class IsProtected(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            mapOf("protected" to rule.mustProtect)
    }
}
