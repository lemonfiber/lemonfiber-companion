package app.lemonfiber.native

import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import java.util.TimeZone

/**
 * Which time zone the phone's clock is set to, on Android.
 *
 * Namespace: `Lemonfiber.Clock.*`
 *
 * One question with one answer the platform always has: the zone's name in the
 * time zone database, such as `Europe/Amsterdam`. PHP on a phone is not told
 * it, and a time shown to somebody holding the phone is owed their own clock.
 *
 * The name is read now rather than once at launch, so a phone carried across a
 * border answers with where it is.
 */
public object ClockFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /**
     * Answer the zone the clock is set to.
     *
     * The activity is what the plugin's registration hands every function, and
     * this one has no use for it: the zone belongs to the device, not a window.
     */
    public class Zone(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val zone = TimeZone.getDefault().id

            Log.d(TAG, "clock: zone read")

            return Envelope.of("known", mapOf("zone" to zone)).asAnswer()
        }
    }
}
