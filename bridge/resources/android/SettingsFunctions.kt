package app.lemonfiber.native

import android.content.ActivityNotFoundException
import android.content.Intent
import android.net.Uri
import android.provider.Settings
import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * This app's own page in the phone's settings, on Android.
 *
 * Namespace: `Lemonfiber.Settings.*`
 *
 * One request: leave the app for its details page, where a permission it was
 * refused is granted again. A phone with nothing to answer the request is a
 * refusal rather than a crash, so the screen can say the page did not open.
 */
public object SettingsFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** The scheme a package is named under in a details-page request. */
    private const val PACKAGE = "package"

    /** Ask for this app's details page, and say whether it opened. */
    public class Open(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val asked =
                try {
                    activity.startActivity(
                        Intent(
                            Settings.ACTION_APPLICATION_DETAILS_SETTINGS,
                            Uri.fromParts(PACKAGE, activity.packageName, null),
                        ),
                    )

                    true
                } catch (failed: ActivityNotFoundException) {
                    Log.d(TAG, "settings: nothing answered, ${failed.javaClass.simpleName}")

                    false
                }

            val said = SettingsRule(pageWasAskedFor = asked).said

            Log.d(TAG, "settings: ${said.word}")

            return Envelope.of(said.word).asAnswer()
        }
    }
}
