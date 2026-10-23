package app.lemonfiber.native

import android.content.Context
import android.util.Log
import androidx.work.Constraints
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.NetworkType
import androidx.work.PeriodicWorkRequest
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.Worker
import androidx.work.WorkerParameters
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeFunctionRegistry
import com.nativephp.mobile.bridge.LaravelEnvironment
import com.nativephp.mobile.bridge.PHPBridge
import java.util.concurrent.TimeUnit

/**
 * Being woken while away, on Android.
 *
 * Namespace: `Lemonfiber.Wakes.*`
 *
 * Periodic work the platform runs when it chooses, no more often than its
 * least interval allows and only while the phone has a network. A wake
 * carries nothing: it boots a short-lived PHP runtime beside whatever else is
 * running and runs [WakeRule.COMMAND], which reads what each
 * stack raised over the app's own pinned connection and tells it locally. No
 * push service is involved.
 *
 * WorkManager keeps the work across a restart of the phone and of the app, so
 * asking once is enough until [Stop].
 */
public object WakeFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** Where NativePHP's bootstrap for a PHP runtime lives, under the app's Laravel directory. */
    private const val BOOTSTRAP = "vendor/nativephp/mobile/bootstrap/android/persistent.php"

    /**
     * The functions a wake reaches with no window open, each made from a context.
     *
     * Registered only where the app's own registration has not already put
     * them there: with the app open, its functions stand; after the platform
     * started the process for a wake alone, nothing registered them.
     */
    private val reachedWithoutAWindow: Map<String, (Context) -> BridgeFunction> =
        mapOf(
            "Lemonfiber.Storage.Keep" to { context -> StorageFunctions.Keep(context) },
            "Lemonfiber.Storage.Read" to { context -> StorageFunctions.Read(context) },
            "Lemonfiber.Storage.Forget" to { context -> StorageFunctions.Forget(context) },
            "Lemonfiber.Telling.Standing" to { context -> TellingFunctions.Standing(context) },
            "Lemonfiber.Telling.Show" to { context -> TellingFunctions.Show(context) },
            "Lemonfiber.Resolve" to { context -> ResolveFunctions.Resolve(context) },
            "Lemonfiber.LocalNetwork.Probe" to { context -> LocalNetworkFunctions.Probe(context) },
        )

    /** `Lemonfiber.Wakes.Ask` — ask to be woken as often as the platform runs periodic work. */
    public class Ask(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val online = Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build()
            val request =
                PeriodicWorkRequestBuilder<Hearing>(
                    PeriodicWorkRequest.MIN_PERIODIC_INTERVAL_MILLIS,
                    TimeUnit.MILLISECONDS,
                ).setConstraints(online).build()

            WorkManager
                .getInstance(context.applicationContext)
                .enqueueUniquePeriodicWork(WakeRule.NAME, ExistingPeriodicWorkPolicy.UPDATE, request)

            Log.d(TAG, "wakes: asked")

            return Envelope.of("will_wake").asAnswer()
        }
    }

    /** `Lemonfiber.Wakes.Stop` — stop asking, and take back the work the platform holds. */
    public class Stop(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            WorkManager.getInstance(context.applicationContext).cancelUniqueWork(WakeRule.NAME)

            Log.d(TAG, "wakes: stopped")

            return Envelope.of("will_not_wake").asAnswer()
        }
    }

    /**
     * One wake: a PHP runtime of its own, the command, and the runtime let go of.
     *
     * Always answers success. A wake that found no runtime or nothing to tell
     * is not one to retry sooner than the next; the platform's own schedule is
     * the retry.
     */
    public class Hearing(
        context: Context,
        parameters: WorkerParameters,
    ) : Worker(context, parameters) {
        override fun doWork(): Result {
            LaravelEnvironment(applicationContext).initializeForBackground()

            val registry = BridgeFunctionRegistry.shared

            reachedWithoutAWindow
                .filterKeys { !registry.exists(it) }
                .forEach { (name, make) -> registry.register(name, make(applicationContext)) }

            val php = PHPBridge(applicationContext)

            if (php.nativeEphemeralBoot("${php.getLaravelPath()}/$BOOTSTRAP") != 0) {
                Log.d(TAG, "wakes: no runtime")

                return Result.success()
            }

            try {
                php.nativeEphemeralArtisan(WakeRule.COMMAND)
            } finally {
                php.nativeEphemeralShutdown()
            }

            Log.d(TAG, "wakes: heard")

            return Result.success()
        }
    }
}
