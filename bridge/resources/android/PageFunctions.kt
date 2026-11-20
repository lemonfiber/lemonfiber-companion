package app.lemonfiber.native

import android.content.ActivityNotFoundException
import android.content.Intent
import android.net.Uri
import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * A page outside the app, opened in the phone's browser, on Android.
 *
 * Namespace: `Lemonfiber.Page.*`
 *
 * One request: hand an https page to whatever browser answers it. Any other
 * address is refused before anything is started, and a phone with nothing to
 * answer the request is a refusal rather than a crash.
 */
public object PageFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** The parameter carrying the address. */
    private const val ADDRESS = "address"

    /** Open the page, and say whether it opened. */
    public class Open(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val rule = PageRule(address = parameters[ADDRESS] as? String ?: "")
            val asked = rule.mayOpen && opened(rule.address)
            val said = rule.said(pageWasAskedFor = asked)

            Log.d(TAG, "page: ${said.word}")

            return Envelope.of(said.word).asAnswer()
        }

        private fun opened(address: String): Boolean =
            try {
                activity.startActivity(
                    Intent(Intent.ACTION_VIEW, Uri.parse(address)).addCategory(Intent.CATEGORY_BROWSABLE),
                )

                true
            } catch (failed: ActivityNotFoundException) {
                Log.d(TAG, "page: nothing answered, ${failed.javaClass.simpleName}")

                false
            }
    }
}
