package app.lemonfiber.native

import android.util.Base64
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * Artwork held in memory, on Android.
 *
 * Namespace: `Lemonfiber.Artwork.*`
 *
 * PHP hands each picture over as base64 under its key and the stack it came
 * from, asks whether a key is still held, and lets go of a stack's pictures
 * when the stack is removed or its session ends. The lock lets go of every
 * picture itself (`TheLock.kt`). No key, stack or byte reaches a log line.
 */
public object ArtworkFunctions {
    private const val HELD = "in-memory"
    private const val REFUSED = "refused"
    private const val LET_GO = "let-go"

    /** Hold a picture under its key, for the stack it came from. */
    public class Hold(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val key = parameters["key"] as? String ?: ""
            val stack = parameters["stack"] as? String ?: ""
            val bytes = (parameters["bytes"] as? String)?.let { decoded(it) } ?: ByteArray(0)

            return if (ArtworkHeld.shared.hold(key, stack, bytes)) {
                Envelope.of(HELD).asAnswer()
            } else {
                Envelope.refusing(REFUSED, "not-a-picture").asAnswer()
            }
        }

        private fun decoded(encoded: String): ByteArray? =
            try {
                Base64.decode(encoded, Base64.DEFAULT)
            } catch (_: IllegalArgumentException) {
                null
            }
    }

    /** Whether a picture is still held under its key. */
    public class Holds(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val key = parameters["key"] as? String ?: ""

            return Envelope.of(HELD, mapOf("holds" to ArtworkHeld.shared.holds(key))).asAnswer()
        }
    }

    /** Let go of every picture held for a stack. */
    public class LetGo(
        @Suppress("UnusedPrivateProperty") private val activity: FragmentActivity,
    ) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            ArtworkHeld.shared.letGoOf(parameters["stack"] as? String ?: "")

            return Envelope.of(LET_GO).asAnswer()
        }
    }
}
