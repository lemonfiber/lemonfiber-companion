package app.lemonfiber.native

import android.content.Context
import android.content.SharedPreferences
import android.util.Log
import androidx.fragment.app.FragmentActivity
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction
import java.io.IOException
import java.security.GeneralSecurityException

/**
 * Keeping a secret, on Android.
 *
 * Namespace: `Lemonfiber.Storage.*`
 *
 * Every session this application holds and every stack it is paired with lives
 * in the platform's encrypted store and nowhere else — not in preferences, not
 * in an app-readable file, not in an unencrypted backup. Those alternatives are
 * absent from this file rather than guarded against, which is the only way to
 * be sure: a fallback written for the device that has no store is the line that
 * writes a token to a file.
 *
 * The decision worth testing is not here. [StorageRule] holds the two
 * distinctions a caller cannot make for itself — a device with no store told
 * from a store that would not open, and a key that is not there told from a
 * store that cannot be asked — and it runs on a JVM with no device in sight.
 *
 * **Nothing secret reaches a log line, a breadcrumb or a cache file.** Not the
 * key, not the value, not a stack's identifier. The log lines here carry the
 * outcome word and the reason word, both closed sets, neither derived from
 * anything a caller passed in. That is what makes the promise checkable by
 * reading this file.
 *
 * **Every platform failure is caught and turned into a word.** A
 * `GeneralSecurityException` message can carry the alias it failed on, and an
 * uncaught one reaches a crash reporter — which is a copy of the thing being
 * protected, in somebody else's database. Catching is not defensive here; it is
 * the requirement.
 */
public object StorageFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** The encrypted file this application's values live in. */
    private const val STORE = "lemonfiber.kept"

    /** The word for a value that was written. */
    private const val KEPT = "kept"

    /** The word for a value that was read. */
    private const val FOUND = "found"

    /** The word for a key the store does not hold. */
    private const val NOTHING = "nothing"

    /** The word for a value that was taken out, or was never in. */
    private const val FORGOTTEN = "forgotten"

    /** The word every refusal this capability makes is called. */
    private const val REFUSED = "refused"

    /**
     * The encrypted store, or nothing and which kind of nothing it was.
     *
     * Opened per call rather than held. The alternative is a handle taken at
     * launch, which on a device whose Keystore was not ready then would answer
     * *no store on this device* for the life of the process — and the operator
     * would be told to give up on a phone that works.
     *
     * The two failures are told apart by which step raised. A master key that
     * cannot be built is a device with no usable Keystore; a master key that
     * builds and a file that will not open is a store that exists and would not
     * open. That is the distinction [StorageRule] carries and it is read here
     * rather than guessed.
     */
    private fun opened(context: Context): Pair<SharedPreferences?, StorageRule> {
        val key =
            masterKey(context)
                ?: return null to StorageRule(storeExists = false, storeOpened = false)

        return try {
            EncryptedSharedPreferences.create(
                context,
                STORE,
                key,
                EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
                EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
            ) to StorageRule.WORKING
        } catch (failed: GeneralSecurityException) {
            Log.d(TAG, "storage: the store would not open, ${failed.javaClass.simpleName}")

            null to StorageRule(storeExists = true, storeOpened = false)
        } catch (failed: IOException) {
            Log.d(TAG, "storage: the store would not open, ${failed.javaClass.simpleName}")

            null to StorageRule(storeExists = true, storeOpened = false)
        }
    }

    /**
     * This application's key in the device's Keystore, or nothing.
     *
     * Its own step because it is the one that separates the two refusals. A key
     * that cannot be built is a device with no usable Keystore, and nothing an
     * operator does will change that; a key that builds is a device that has
     * one, whatever the file does next.
     *
     * The log carries the exception's *class* and never its message: a
     * `GeneralSecurityException` message can name the alias it failed on.
     */
    private fun masterKey(context: Context): MasterKey? =
        try {
            MasterKey.Builder(context).setKeyScheme(MasterKey.KeyScheme.AES256_GCM).build()
        } catch (failed: GeneralSecurityException) {
            Log.d(TAG, "storage: no master key, ${failed.javaClass.simpleName}")

            null
        } catch (failed: IOException) {
            Log.d(TAG, "storage: no master key, ${failed.javaClass.simpleName}")

            null
        }

    /**
     * What one read came back with, as the wire says it.
     *
     * Its own step so that *there is none* and *nobody could be asked* are
     * written apart from each other rather than as two arms of one expression —
     * they are the distinction this whole capability exists to keep, and the
     * one a reader should be able to find.
     */
    private fun whatWasHeld(held: String?): Map<String, Any> {
        if (held == null) {
            Log.d(TAG, "storage: nothing under that key")

            return Envelope.of(NOTHING).asAnswer()
        }

        Log.d(TAG, "storage: found")

        return Envelope.of(FOUND, mapOf("value" to held)).asAnswer()
    }

    /**
     * The refusal for a store that could not be opened.
     *
     * The reason comes off the rule rather than from whichever `catch` ran, so
     * that the word a caller reads and the word the rule decided are the same
     * word by construction.
     */
    private fun refusing(rule: StorageRule): Map<String, Any> {
        val why = rule.whyNot ?: WhyNothingWasKept.STORE_WOULD_NOT_OPEN

        Log.d(TAG, "storage: refused, ${why.word}")

        return Envelope.refusing(REFUSED, why.word).asAnswer()
    }

    /** The key a call named, or a refusal to guess at one. */
    private fun keyIn(parameters: Map<String, Any>): String =
        parameters["key"] as? String ?: throw BridgeError.InvalidParameters("key is required")

    /**
     * `Lemonfiber.Storage.Keep` — write one value, or say why not.
     *
     * The moment it may be read again is taken from the caller and recorded
     * even though this platform has one behaviour, because a choice made on one
     * platform and defaulted on the other is a choice nobody can see being
     * made. Android's encrypted store is readable whenever the application can
     * run, which is *after first unlock* — so a caller asking for the narrower
     * one is asking for something this platform cannot give, and the honest
     * thing is to write down which it got rather than to pretend.
     */
    public class Keep(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val (store, rule) = opened(activity)
            val value =
                parameters["value"] as? String
                    ?: throw BridgeError.InvalidParameters("value is required")

            if (store == null || !rule.mayProceed) {
                return refusing(rule)
            }

            store.edit().putString(keyIn(parameters), value).commit()

            Log.d(TAG, "storage: kept, readable ${WhenAValueMayBeRead.AFTER_FIRST_UNLOCK.word}")

            return Envelope
                .of(KEPT, mapOf("readable" to WhenAValueMayBeRead.AFTER_FIRST_UNLOCK.word))
                .asAnswer()
        }
    }

    /**
     * `Lemonfiber.Storage.Read` — read one value, or say there is none.
     *
     * *There is none* and *nobody could be asked* are different answers and this
     * is the function where the difference costs the most: a launch reading a
     * refusal as an empty store offers to pair a machine that is already paired.
     */
    public class Read(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val (store, rule) = opened(activity)

            if (store == null || !rule.mayProceed) {
                return refusing(rule)
            }

            return whatWasHeld(store.getString(keyIn(parameters), null))
        }
    }

    /**
     * `Lemonfiber.Storage.Forget` — take one value out.
     *
     * Forgetting a key that was never kept is `forgotten` rather than an error.
     * It is the ordinary case after a refused write, and getting rid of a
     * session is the one operation that must always work — including on the
     * device where keeping it did not.
     */
    public class Forget(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val (store, rule) = opened(activity)

            if (store == null || !rule.mayProceed) {
                return refusing(rule)
            }

            store.edit().remove(keyIn(parameters)).commit()

            Log.d(TAG, "storage: forgotten")

            return Envelope.of(FORGOTTEN).asAnswer()
        }
    }
}
