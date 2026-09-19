package app.lemonfiber.native

import android.content.Context
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * Whether there is a link, on Android.
 *
 * Namespace: `Lemonfiber.Link.*`
 *
 * One question: can this device reach anything at all right now. The answer
 * lets the app tell *this phone has no network* from *that machine is not
 * answering*, which are two sentences with two different remedies and which a
 * socket timing out cannot tell apart.
 *
 * The decision worth testing is not here. [LinkRule] holds it — including the
 * one that matters, that a platform which could not be asked is read as a link
 * that works — and it runs on a JVM with no device in sight.
 *
 * **The kind of link is never read.** The platform reports whether it is wifi,
 * cellular or ethernet, whether it is metered, and whether Low Data Mode is on.
 * None of it is asked for here and the envelope has no place to put it, so
 * adding one means explaining why rather than uncommenting a line.
 *
 * **Validation is deliberately not asked for.** `NET_CAPABILITY_VALIDATED`
 * means a probe reached the internet, and this application talks to a machine
 * in the operator's house. A phone on a wifi network with no uplink can still
 * reach the stack in the next room, and reading validation would report *no
 * network* to somebody standing five metres from their own.
 *
 * **Nothing about the device reaches a log line.** Not an SSID, not an
 * interface name, not a carrier, not an address. The one line here carries the
 * outcome word, which is one of two.
 */
public object LinkFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /**
     * What the platform says about the link, as the rule's three facts.
     *
     * Every failure is caught and answered as *could not be asked* rather than
     * allowed out. A device that cannot answer this question is not a device
     * with no network, and an uncaught throw here would take down a launch over
     * a question whose honest answer is *try anyway*.
     */
    private fun asKnown(context: Context): LinkRule =
        try {
            val manager = context.getSystemService(ConnectivityManager::class.java)
            val active = manager?.activeNetwork
            val capabilities = active?.let { manager.getNetworkCapabilities(it) }

            LinkRule(
                linkWasReadable = manager != null,
                hasAnActiveLink = active != null,
                carriesTraffic =
                    capabilities?.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) == true,
            )
        } catch (failed: SecurityException) {
            Log.d(TAG, "link: could not be asked, ${failed.javaClass.simpleName}")

            LinkRule.UNREADABLE
        }

    /** Answer whether anything is reachable from here. */
    public class Status(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val said = asKnown(activity).said

            Log.d(TAG, "link: ${said.word}")

            return Envelope.of(said.word).asAnswer()
        }
    }
}
