package app.lemonfiber.native

import android.content.ClipData
import android.content.Intent
import android.util.Log
import androidx.core.content.FileProvider
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import java.io.File
import java.util.Base64

/**
 * Handing something over, on Android.
 *
 * Namespace: `Lemonfiber.Handover.*`
 *
 * The platform's own chooser, given text or a file to put in front of somebody
 * who can help. Where it goes is a choice a person makes in an app this one
 * does not know about, which is the whole difference between this and the
 * crash reporter this application may not have.
 *
 * The decisions worth testing are not here. [HandoverRule] holds the two
 * refusals and the order they are read in, and [ShareCache] holds where a file
 * is written and how it is swept. Both run on a JVM with no device in sight.
 *
 * **Text is sent as text.** `EXTRA_TEXT` goes straight into whatever the
 * operator picks, and nothing is written.
 *
 * **A file is written once, app-private, and swept.** `OfferFile` writes the
 * bytes into [ShareCache]'s one directory in this app's own cache, and hands
 * the chooser a `content://` URI from the host's `FileProvider` with
 * `FLAG_GRANT_READ_URI_PERMISSION`: the chosen app may read that one file and
 * nothing else. Nothing here learns when it is done reading, so the directory
 * is emptied before every new handover and on the next launch: at most one
 * file is ever in it.
 *
 * **What the operator chose is never asked for.** `startActivity` on a chooser
 * answers nothing about the choice, and the API that would (`createChooser`
 * with an `IntentSender`) is deliberately not used: reporting which app
 * received a diagnostic report would be this application learning something
 * about the operator it has no reason to know.
 *
 * **Nothing handed over reaches a log line.** Not the title, not the file's
 * name, not a byte of the text or the file. The one line here carries the
 * outcome word.
 */
public object HandoverFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** The word the sheet having been presented is called. */
    private const val OFFERED = "offered"

    /** What a caller names the report. */
    private const val TITLE = "title"

    /** What a caller sends as the report itself. */
    private const val TEXT = "text"

    /** What a caller names the file. */
    private const val NAME = "name"

    /** What a caller sends as the file's bytes, in base64. */
    private const val BYTES = "bytes"

    /** The word every refusal this capability makes is called. */
    private const val REFUSED = "refused"

    /**
     * The host app's `FileProvider`, after its application id.
     *
     * The NativePHP host declares it over the whole of its cache directory, so
     * the share cache inside that directory is served without a provider of
     * this plugin's own.
     */
    private const val PROVIDER = ".fileprovider"

    /** What a file whose type the provider cannot tell is offered as. */
    private const val ANY_FILE = "application/octet-stream"

    /** What the rule decided, as the answer the bridge hands back. */
    private fun answer(rule: HandoverRule): Map<String, Any> {
        val why = rule.whyNot

        if (why != null) {
            Log.d(TAG, "handover: refused, ${why.word}")

            return Envelope.refusing(REFUSED, why.word).asAnswer()
        }

        Log.d(TAG, "handover: $OFFERED")

        return Envelope.of(OFFERED).asAnswer()
    }

    /**
     * Ask for the chooser, and say whether it opened.
     *
     * Every failure is caught and answered as a word. An uncaught throw here
     * would take down the screen an operator pressed *send this to somebody*
     * on, and what they need instead is a sentence telling them it did not
     * open.
     */
    private fun present(
        activity: FragmentActivity,
        sending: Intent,
        title: String,
    ): Boolean =
        try {
            // `createChooser` carries a read grant on the intent it wraps, with
            // its `ClipData`, over to the chooser, so a file's grant reaches
            // whichever app is picked and text needs none.
            activity.startActivity(Intent.createChooser(sending, title))

            true
        } catch (failed: android.content.ActivityNotFoundException) {
            Log.d(TAG, "handover: no chooser, ${failed.javaClass.simpleName}")

            false
        }

    /** Put a report in front of whoever the operator picks. */
    public class Offer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val title = parameters[TITLE] as? String ?: ""
            val text = parameters[TEXT] as? String ?: ""

            val sending =
                Intent(Intent.ACTION_SEND).apply {
                    type = "text/plain"
                    putExtra(Intent.EXTRA_TITLE, title)
                    putExtra(Intent.EXTRA_SUBJECT, title)
                    putExtra(Intent.EXTRA_TEXT, text)
                }

            return answer(
                HandoverRule(
                    thereIsSomethingToHandOver = text.isNotEmpty(),
                    theSheetWasPresented = text.isNotEmpty() && present(activity, sending, title),
                ),
            )
        }
    }

    /**
     * Write a file into the share cache and put it in front of whoever the
     * operator picks.
     *
     * The bytes arrive as base64, because the bridge carries JSON. Bytes that
     * do not decode, a name that is not one file's name, and a file that could
     * not be written are all *nothing to hand over*: the chooser is never asked.
     */
    public class OfferFile(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val title = parameters[TITLE] as? String ?: ""
            val name = parameters[NAME] as? String ?: ""
            val encoded = parameters[BYTES] as? String ?: ""
            val bytes = runCatching { Base64.getDecoder().decode(encoded) }.getOrNull()
            val cache = ShareCache.inside(activity.cacheDir)
            val written = cache.write(name, bytes ?: ByteArray(0))

            return answer(
                HandoverRule(
                    thereIsSomethingToHandOver = written != null,
                    theSheetWasPresented = written != null && cache.holds(written) && grant(written, title),
                ),
            )
        }

        /**
         * Hand the chooser a read grant for that one file, and say whether it
         * opened.
         *
         * A file the host's provider will not serve is a chooser that did not
         * open, answered as a word for the reason [present] catches.
         */
        private fun grant(
            file: File,
            title: String,
        ): Boolean {
            val uri =
                runCatching {
                    FileProvider.getUriForFile(activity, activity.packageName + PROVIDER, file)
                }.getOrNull() ?: return false

            val sending =
                Intent(Intent.ACTION_SEND).apply {
                    type = activity.contentResolver.getType(uri) ?: ANY_FILE
                    putExtra(Intent.EXTRA_TITLE, title)
                    putExtra(Intent.EXTRA_SUBJECT, title)
                    putExtra(Intent.EXTRA_STREAM, uri)
                    clipData = ClipData.newRawUri(title, uri)
                    addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                }

            return present(activity, sending, title)
        }
    }
}
