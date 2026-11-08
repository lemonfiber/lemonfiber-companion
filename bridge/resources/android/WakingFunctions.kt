package app.lemonfiber.native

import android.content.Context
import android.content.Intent
import android.content.SharedPreferences
import android.content.pm.PackageManager
import android.os.Build
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import android.util.Log
import androidx.work.Constraints
import androidx.work.Data
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction
import org.unifiedpush.android.connector.FailedReason
import org.unifiedpush.android.connector.MessagingReceiver
import org.unifiedpush.android.connector.UnifiedPush
import org.unifiedpush.android.connector.data.PublicKeySet
import org.unifiedpush.android.connector.data.PushEndpoint
import org.unifiedpush.android.connector.data.PushMessage
import org.unifiedpush.android.connector.keys.KeyManager
import java.security.GeneralSecurityException
import java.security.KeyPairGenerator
import java.security.KeyStore
import java.security.interfaces.ECPublicKey
import java.security.spec.ECGenParameterSpec
import java.util.concurrent.CompletableFuture
import java.util.concurrent.ConcurrentHashMap
import java.util.concurrent.Executors
import java.util.concurrent.TimeUnit
import java.util.concurrent.TimeoutException

/**
 * Being woken through a UnifiedPush distributor, on Android.
 *
 * Namespace: `Lemonfiber.Waking.*`
 *
 * Each stack is an instance of its own with the distributor, named by the
 * stack's id, and has a WebPush key pair made for it alone in the keystore,
 * for key agreement and never exportable. The connector holds no key: it is
 * handed [HoldsNoKeys], so it neither makes nor keeps one, and no push is
 * decrypted or read. A push only queues the wake for its stack.
 *
 * The newest endpoint the distributor gave each stack is kept in the app's own
 * preferences, so a wake can register a changed one with the stack.
 */
public object WakingFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** Where the newest endpoint for each stack is kept. */
    private const val ENDPOINTS = "lemonfiber.waking"

    /** The input a queued wake reads its stack from. */
    public const val STACK: String = "stack"

    /** Where the keystore keeps keys. */
    private const val KEYSTORE = "AndroidKeyStore"

    /** The curve WebPush keys are on. */
    private const val CURVE = "secp256r1"

    /** Each registration being waited for, answered with its endpoint or with why none came. */
    private val waiting = ConcurrentHashMap<String, CompletableFuture<Result<String>>>()

    /** Where the receiver does its work, off the main thread. */
    private val worker = Executors.newSingleThreadExecutor()

    private fun endpoints(context: Context): SharedPreferences =
        context.applicationContext.getSharedPreferences(ENDPOINTS, Context.MODE_PRIVATE)

    private fun stackIn(parameters: Map<String, Any>): String =
        (parameters[STACK] as? String)?.takeIf { WakingRule.isAStack(it) }
            ?: throw BridgeError.InvalidParameters("stack is required")

    private fun notHere(): Map<String, Any> =
        Envelope.refusing(WakingRule.NOT_GIVEN, WakingRule.NOT_HERE).asAnswer()

    private fun given(
        endpoint: String,
        key: ByteArray,
    ): Map<String, Any> =
        Envelope
            .of(
                WakingRule.GIVEN,
                mapOf("endpoint" to endpoint, "p256dh" to Base64.encodeToString(key, Base64.NO_WRAP)),
            ).asAnswer()

    /** Drop the stack's registration, its key pair and its endpoint. */
    private fun letGoOf(
        context: Context,
        stack: String,
    ) {
        UnifiedPush.unregister(context, stack, HoldsNoKeys)
        endpoints(context).edit().remove(stack).apply()

        try {
            Keys.keystore().deleteEntry(WakingRule.aliasFor(stack))
        } catch (failed: GeneralSecurityException) {
            Log.d(TAG, "waking: key not deleted, ${failed.javaClass.simpleName}")
        }
    }

    /** Queue the wake for one stack, after any still queued for it, once the phone has a network. */
    private fun wake(
        context: Context,
        stack: String,
    ) {
        val online = Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build()
        val request =
            OneTimeWorkRequestBuilder<WakeFunctions.Hearing>()
                .setConstraints(online)
                .setInputData(Data.Builder().putString(STACK, stack).build())
                .build()

        WorkManager
            .getInstance(context.applicationContext)
            .enqueueUniqueWork(WakingRule.workFor(stack), ExistingWorkPolicy.APPEND_OR_REPLACE, request)
    }

    /** The WebPush key pair of each stack, in the keystore, for key agreement and never exportable. */
    private object Keys {
        fun keystore(): KeyStore = KeyStore.getInstance(KEYSTORE).apply { load(null) }

        /** The stack's key pair, made afresh, as its public point. */
        fun made(stack: String): ByteArray {
            val spec =
                KeyGenParameterSpec
                    .Builder(WakingRule.aliasFor(stack), KeyProperties.PURPOSE_AGREE_KEY)
                    .setAlgorithmParameterSpec(ECGenParameterSpec(CURVE))
                    .build()
            val generator = KeyPairGenerator.getInstance(KeyProperties.KEY_ALGORITHM_EC, KEYSTORE)

            generator.initialize(spec)

            return point(generator.generateKeyPair().public as ECPublicKey)
        }

        /** The stack's public point, where its key pair is held. */
        fun held(stack: String): ByteArray? =
            try {
                val key = keystore().getCertificate(WakingRule.aliasFor(stack))?.publicKey

                (key as? ECPublicKey)?.let { point(it) }
            } catch (failed: GeneralSecurityException) {
                Log.d(TAG, "waking: no key held, ${failed.javaClass.simpleName}")
                null
            }

        private fun point(key: ECPublicKey): ByteArray = WakingRule.uncompressed(key.w.affineX, key.w.affineY)

        /** Delete every key pair made for waking. */
        fun deleteEveryKey() {
            try {
                val store = keystore()

                store
                    .aliases()
                    .toList()
                    .filter { it.startsWith(WakingRule.ALIAS) }
                    .forEach { store.deleteEntry(it) }
            } catch (failed: GeneralSecurityException) {
                Log.d(TAG, "waking: keys not deleted, ${failed.javaClass.simpleName}")
            }
        }
    }

    /** The connector's key manager, which holds no key: the phone's own are made above. */
    public object HoldsNoKeys : KeyManager {
        override fun decrypt(
            instance: String,
            sealed: ByteArray,
        ): ByteArray? = null

        override fun generate(instance: String): Unit = Unit

        override fun getPublicKeySet(instance: String): PublicKeySet? = null

        override fun exists(instance: String): Boolean = true

        override fun delete(instance: String): Unit = Unit
    }

    /** `Lemonfiber.Waking.Distributors` — each distributor installed, with what it is called. */
    public class Distributors(
        private val context: Context,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            if (!WakingRule.canWakeOn(Build.VERSION.SDK_INT)) {
                return Envelope.of(WakingRule.NOT_HERE).asAnswer()
            }

            val listed =
                UnifiedPush
                    .getDistributors(context)
                    .filter { it != context.packageName }
                    .map { mapOf("package" to it, "called" to called(it)) }

            return Envelope.of(WakingRule.LISTED, mapOf("distributors" to listed)).asAnswer()
        }

        private fun called(distributor: String): String =
            try {
                context.packageManager
                    .getApplicationLabel(context.packageManager.getApplicationInfo(distributor, 0))
                    .toString()
            } catch (_: PackageManager.NameNotFoundException) {
                distributor
            }
    }

    /** `Lemonfiber.Waking.Register` — make the stack's key pair, and wait for an endpoint. */
    public class Register(
        private val context: Context,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            if (WakingRule.canWakeOn(Build.VERSION.SDK_INT)) {
                registered(stackIn(parameters), distributorIn(parameters))
            } else {
                notHere()
            }

        private fun distributorIn(parameters: Map<String, Any>): String =
            parameters["distributor"] as? String
                ?: throw BridgeError.InvalidParameters("distributor is required")

        private fun registered(
            stack: String,
            distributor: String,
        ): Map<String, Any> {
            val key =
                try {
                    Keys.made(stack)
                } catch (failed: GeneralSecurityException) {
                    Log.d(TAG, "waking: no key, ${failed.javaClass.simpleName}")
                    null
                }
            val endpoint =
                key?.let { endpointFrom(stack, distributor) }
                    ?: Result.failure(Unanswered(WakingRule.NO_KEY))

            return endpoint.fold(
                onSuccess = { given(it, key ?: ByteArray(0)) },
                onFailure = {
                    letGoOf(context, stack)

                    Envelope.refusing(WakingRule.NOT_GIVEN, it.message ?: WakingRule.REFUSED).asAnswer()
                },
            )
        }

        private fun endpointFrom(
            stack: String,
            distributor: String,
        ): Result<String> {
            val answer = CompletableFuture<Result<String>>()

            waiting[stack] = answer
            UnifiedPush.saveDistributor(context, distributor)
            UnifiedPush.register(context, stack, WakingRule.MESSAGE_FOR_DISTRIBUTOR, null, HoldsNoKeys)

            return try {
                answer.get(WakingRule.WAIT_SECONDS, TimeUnit.SECONDS)
            } catch (late: TimeoutException) {
                Log.d(TAG, "waking: ${late.javaClass.simpleName}")
                Result.failure(Unanswered(WakingRule.NO_ANSWER))
            } finally {
                waiting.remove(stack)
            }
        }
    }

    /** `Lemonfiber.Waking.Endpoint` — the endpoint the distributor gives the stack now, and its key. */
    public class Endpoint(
        private val context: Context,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            if (!WakingRule.canWakeOn(Build.VERSION.SDK_INT)) {
                return notHere()
            }

            val stack = stackIn(parameters)
            val endpoint = endpoints(context).getString(stack, null)
            val key = endpoint?.let { Keys.held(stack) }

            return if (endpoint != null && key != null) {
                given(endpoint, key)
            } else {
                Envelope.refusing(WakingRule.NOT_GIVEN, WakingRule.DROPPED).asAnswer()
            }
        }
    }

    /** Why a registration came to nothing, in [WakingRule]'s word. */
    private class Unanswered(
        word: String,
    ) : IllegalStateException(word)

    /** `Lemonfiber.Waking.LetGo` — drop the stack's registration and key pair. */
    public class LetGo(
        private val context: Context,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            if (WakingRule.canWakeOn(Build.VERSION.SDK_INT)) {
                letGoOf(context, stackIn(parameters))
            }

            return Envelope.of(WakingRule.LET_GO).asAnswer()
        }
    }

    /** `Lemonfiber.Waking.LetGoOfEvery` — drop every registration and every key pair made for waking. */
    public class LetGoOfEvery(
        private val context: Context,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            if (WakingRule.canWakeOn(Build.VERSION.SDK_INT)) {
                UnifiedPush.removeDistributor(context, HoldsNoKeys)
                endpoints(context).edit().clear().apply()
                Keys.deleteEveryKey()
            }

            return Envelope.of(WakingRule.LET_GO).asAnswer()
        }
    }

    /**
     * What a distributor says to this app: an endpoint, a refusal, a registration dropped, or a push.
     *
     * Declared in the manifest above the connector's own receiver, which then
     * stands down, so the connector is handed [HoldsNoKeys] on every path.
     */
    public class Receiver : MessagingReceiver() {
        override fun getKeyManager(context: Context): KeyManager = HoldsNoKeys

        override fun onReceive(
            context: Context,
            intent: Intent,
        ) {
            val pending = goAsync()

            worker.submit {
                try {
                    super.onReceive(context, intent)
                } finally {
                    pending.finish()
                }
            }
        }

        override fun onNewEndpoint(
            context: Context,
            endpoint: PushEndpoint,
            instance: String,
        ) {
            if (!WakingRule.isAStack(instance)) {
                return
            }

            val previous = endpoints(context).getString(instance, null)
            val answer = waiting[instance]

            endpoints(context).edit().putString(instance, endpoint.url).apply()
            answer?.complete(Result.success(endpoint.url))

            if (WakingRule.wakesForANewEndpoint(previous, endpoint.url, awaited = answer != null)) {
                wake(context, instance)
            }
        }

        override fun onRegistrationFailed(
            context: Context,
            reason: FailedReason,
            instance: String,
        ) {
            Log.d(TAG, "waking: refused, $reason")
            waiting[instance]?.complete(Result.failure(Unanswered(WakingRule.REFUSED)))
        }

        override fun onUnregistered(
            context: Context,
            instance: String,
        ) {
            if (!WakingRule.isAStack(instance)) {
                return
            }

            endpoints(context).edit().remove(instance).apply()
            wake(context, instance)
        }

        override fun onMessage(
            context: Context,
            message: PushMessage,
            instance: String,
        ) {
            if (WakingRule.isAStack(instance)) {
                wake(context, instance)
            }
        }
    }
}
