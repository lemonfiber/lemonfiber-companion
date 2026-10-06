package app.lemonfiber.native

import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import java.net.Inet4Address
import java.net.Inet6Address
import java.net.InetAddress
import java.util.concurrent.ExecutionException
import java.util.concurrent.Executors
import java.util.concurrent.TimeUnit
import java.util.concurrent.TimeoutException

/**
 * Look a machine's name up the way the phone itself does, on Android.
 *
 * Namespace: `Lemonfiber.Resolve`
 *
 * The app's runtime looks names up through its own resolver, and that one does
 * not answer a `.local` name. Android's does: `InetAddress` asks the system
 * resolver, which answers `.local` names over multicast DNS. This asks it, and
 * hands back what it found for the app to send to while keeping the name.
 *
 * **Never on the thread that called.** The look-up runs on a thread of its
 * own, and the call waits for it only so long: a name nothing answers for is
 * otherwise a wait of several seconds.
 *
 * The decision worth testing is not here. `ResolveRule` holds it, and it runs
 * on a JVM with no device in sight.
 *
 * **Nothing about the name or what it resolved to reaches a log line.** The
 * lines here carry the outcome word, how many addresses are usable, and the
 * class of what the resolver raised.
 */
public object ResolveFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** How long to wait for the resolver before giving up on the name. */
    private const val LONG_ENOUGH_FOR_AN_ANSWER_MS = 3_000L

    /** Where look-ups run, so none ever runs on the thread that asked. */
    private val lookingUp = Executors.newCachedThreadPool()

    /** What the system resolver says about one name, as the rule's facts. */
    private fun asFound(host: String): ResolveRule {
        if (host.isEmpty()) {
            return ResolveRule.NOTHING_FOUND
        }

        val asked = lookingUp.submit<Array<InetAddress>> { InetAddress.getAllByName(host) }

        return try {
            ResolveRule(
                asked.get(LONG_ENOUGH_FOR_AN_ANSWER_MS, TimeUnit.MILLISECONDS).map {
                    AnAddressFound(
                        numeric = it.hostAddress.orEmpty(),
                        isVersion4 = it is Inet4Address,
                        needsAnInterface = it is Inet6Address && it.isLinkLocalAddress,
                    )
                },
            )
        } catch (failed: ExecutionException) {
            Log.d(TAG, "resolve: not found, ${failed.cause?.javaClass?.simpleName}")

            ResolveRule.NOTHING_FOUND
        } catch (failed: TimeoutException) {
            asked.cancel(true)
            Log.d(TAG, "resolve: no answer in time, ${failed.javaClass.simpleName}")

            ResolveRule.NOTHING_FOUND
        } catch (failed: InterruptedException) {
            asked.cancel(true)
            Thread.currentThread().interrupt()
            Log.d(TAG, "resolve: interrupted, ${failed.javaClass.simpleName}")

            ResolveRule.NOTHING_FOUND
        }
    }

    /**
     * Answer what one name resolves to.
     *
     * The activity is what the plugin's registration hands every function, and
     * this one has no use for it: the resolver is the system's.
     */
    public class Resolve(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val rule = asFound(parameters["host"] as? String ?: "")

            Log.d(TAG, "resolve: ${rule.said.word}, ${rule.usable.size} usable")

            return Envelope.of(rule.said.word, carrying = mapOf("addresses" to rule.usable)).asAnswer()
        }
    }
}
