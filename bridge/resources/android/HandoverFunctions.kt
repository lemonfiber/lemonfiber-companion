package app.lemonfiber.native

import android.content.Intent
import android.util.Log
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * Handing something over, on Android.
 *
 * Namespace: `Lemonfiber.Handover.*`
 *
 * The platform's own chooser, given a report to put in front of somebody who
 * can help. Where it goes is a choice a person makes in an app this one does
 * not know about, which is the whole difference between this and the crash
 * reporter this application may not have.
 *
 * The decision worth testing is not here. [HandoverRule] holds it — the two
 * refusals and the order they are read in — and it runs on a JVM with no device
 * in sight.
 *
 * **It sends text and writes no file.** `ACTION_SEND` will take a `content://`
 * URI, and taking one would mean writing a diagnostic report into a cache
 * directory, standing up a `FileProvider` to grant read access to it, and
 * leaving the file there — because nothing here ever learns when the chosen app
 * is done with it. `EXTRA_TEXT` needs none of that. Nothing in this bridge
 * writes to a cache, and this is the one function that would have.
 *
 * **What the operator chose is never asked for.** `startActivity` on a chooser
 * answers nothing about the choice, and the API that would (`createChooser`
 * with an `IntentSender`) is deliberately not used: reporting which app
 * received a diagnostic report would be this application learning something
 * about the operator it has no reason to know.
 *
 * **Nothing of the report reaches a log line.** Not the title, not a byte of the
 * text. The one line here carries the outcome word.
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

    /** The word every refusal this capability makes is called. */
    private const val REFUSED = "refused"

    /** Put a report in front of whoever the operator picks. */
    public class Offer(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val title = parameters[TITLE] as? String ?: ""
            val text = parameters[TEXT] as? String ?: ""

            val rule =
                HandoverRule(
                    thereIsSomethingToHandOver = text.isNotEmpty(),
                    theSheetWasPresented = text.isNotEmpty() && present(title, text),
                )

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
         * Every failure is caught and answered as a word. An uncaught throw
         * here would take down the screen an operator pressed *send this to
         * somebody* on, and what they need instead is a sentence telling them
         * it did not open.
         */
        private fun present(
            title: String,
            text: String,
        ): Boolean =
            try {
                val sending =
                    Intent(Intent.ACTION_SEND).apply {
                        type = "text/plain"
                        putExtra(Intent.EXTRA_TITLE, title)
                        putExtra(Intent.EXTRA_SUBJECT, title)
                        putExtra(Intent.EXTRA_TEXT, text)
                    }

                activity.startActivity(Intent.createChooser(sending, title))

                true
            } catch (failed: android.content.ActivityNotFoundException) {
                Log.d(TAG, "handover: no chooser, ${failed.javaClass.simpleName}")

                false
            }
    }
}
