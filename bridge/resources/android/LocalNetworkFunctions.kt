package app.lemonfiber.native

import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * Whether the platform refuses this app the local network, on Android.
 *
 * Namespace: `Lemonfiber.LocalNetwork.*`
 *
 * Android asks no permission before an app reaches the local network, so there
 * is nothing to ask the platform and the answer is always the one a platform
 * that said nothing gives: open. The function exists so both halves answer the
 * same call, and so the day Android asks, this is the one place that learns to.
 *
 * **Nothing about the address is read or logged.**
 */
public object LocalNetworkFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /**
     * Answer whether the platform refuses this app the way to one address.
     *
     * The activity is what the plugin's registration hands every function, and
     * this one has no use for it: there is nothing on this platform to ask.
     */
    public class Probe(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val said = LocalNetworkRule.UNASKED.said

            Log.d(TAG, "local network: ${said.word}")

            return Envelope.of(said.word).asAnswer()
        }
    }
}
