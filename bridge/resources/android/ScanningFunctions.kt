package app.lemonfiber.native

import android.Manifest
import android.content.Context
import android.content.Intent
import android.content.SharedPreferences
import android.content.pm.PackageManager
import android.util.Log
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import java.util.concurrent.ArrayBlockingQueue
import java.util.concurrent.TimeUnit

/**
 * Reading a pairing code off a screen, on Android.
 *
 * Namespace: `Lemonfiber.Scanning.*`
 *
 * The decision worth testing is not in this file. [CameraRule] holds what the
 * platform's facts mean together — a missing lens told from a refused one, and
 * a refusal that can be put again told from one that has been settled — and it
 * runs on a JVM with no device in sight. What is here is the part that cannot
 * be tested off a handset: reading those facts, raising the prompt, and opening
 * the camera.
 *
 * **The code that was read comes back in the answer to this call, and nowhere
 * else.** Not on an event. On this platform an event is written to the PHP
 * queue *and*, unless its name begins with `__`, injected into the WebView as
 * JavaScript: it becomes a DOM `CustomEvent` any script on the page can listen
 * for, a `Livewire.dispatch`, and an HTTP POST to `/_native/api/events`. A
 * bridge answer is a JNI string return that touches none of that. Pairing
 * material is the one payload in this application where that difference is a
 * disclosure rather than a detail, so it travels the narrow road.
 *
 * **Nothing read is logged, at any level.** Not truncated, not hashed, not the
 * first characters. The log lines here carry closed words — an outcome, a
 * refusal — and nothing that came off a camera, which is what makes the promise
 * checkable by reading the file rather than by trusting whoever wrote it.
 */
public object ScanningFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** Where the record of what this application has asked for is kept. */
    private const val STORE = "lemonfiber.scanning"

    /** Whether the camera prompt has ever been raised by this application. */
    private const val EVER_ASKED = "ever_asked"

    /** The code the permission result is reported back under. */
    private const val ASKING = 0x1EA1

    /** How long the scanner is waited on before it is treated as closed. */
    private const val PATIENCE_MINUTES = 10L

    /** The capability's word for having read something. */
    private const val READ = "read"

    /** The capability's word for having read nothing. */
    private const val NOTHING = "nothing"

    /** Where the scanner leaves what it saw, for the call that is waiting. */
    private val answered = ArrayBlockingQueue<String>(1)

    /** Where the record of what has been asked lives. */
    private val Context.remembered: SharedPreferences
        get() = getSharedPreferences(STORE, Context.MODE_PRIVATE)

    /**
     * What the platform reports, gathered into the four facts the rule reads.
     *
     * `PackageManager.FEATURE_CAMERA_ANY` rather than the rear-camera feature:
     * a tablet with only a front lens can still be pointed at a screen, and
     * refusing it would be this application inventing a hardware requirement
     * the operator does not have.
     */
    private fun rule(activity: FragmentActivity): CameraRule =
        CameraRule(
            cameraExists =
                activity.packageManager.hasSystemFeature(PackageManager.FEATURE_CAMERA_ANY),
            permissionIsGranted =
                ContextCompat.checkSelfPermission(activity, Manifest.permission.CAMERA) ==
                    PackageManager.PERMISSION_GRANTED,
            wouldExplain =
                ActivityCompat.shouldShowRequestPermissionRationale(
                    activity,
                    Manifest.permission.CAMERA,
                ),
            everAsked = activity.remembered.getBoolean(EVER_ASKED, false),
        )

    /**
     * Raise the camera prompt and wait for it to go away.
     *
     * The record is written before the dialog goes up rather than after it is
     * answered, because a process killed while the prompt is on screen has
     * still asked — and a record written afterwards would let this application
     * ask that operator again on every launch.
     */
    private fun ask(activity: FragmentActivity) {
        activity.remembered.edit().putBoolean(EVER_ASKED, true).apply()

        askFor(activity, Manifest.permission.CAMERA, ASKING)
    }

    /**
     * Open the scanner and wait for it to close.
     *
     * The wait is bounded because an unbounded one is a thread this application
     * can never get back: a scanner left open on a bench holds the call
     * forever, and the operator who comes back to it has an application that
     * answers nothing. Ten minutes is far longer than reading a code takes and
     * far shorter than a working day.
     */
    private fun scan(
        activity: FragmentActivity,
        prompt: String,
    ): String? {
        answered.clear()

        activity.startActivity(
            Intent(activity, ScanningActivity::class.java).putExtra(ScanningActivity.PROMPT, prompt),
        )

        return answered.poll(PATIENCE_MINUTES, TimeUnit.MINUTES)
    }

    /** Where the scanner hands back what it read, or that it read nothing. */
    internal fun finished(payload: String?) {
        answered.offer(payload ?: "")
    }

    /**
     * `Lemonfiber.Scanning.Read` — open the camera and read one pairing code.
     *
     * Blocks until the scanner closes, which is what lets the code come back in
     * the answer rather than on an event. The bridge calls this off the main
     * thread — the same arrangement the notification prompt already relies on —
     * so the camera has a thread to run on while this one waits.
     */
    public class Read(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val prompt = parameters["prompt"] as? String ?: ""

            if (rule(activity).mayAsk) {
                ask(activity)
            }

            val settled = rule(activity)

            return if (settled.mayOpen) whatWasRead(scan(activity, prompt)) else refused(settled)
        }
    }

    /**
     * The answer where the camera will not open at all.
     *
     * The fallback reason is reachable rather than defensive: a prompt the
     * operator walks away from leaves the permission undecided, the wait above
     * gives up after two minutes, and what comes back here is a rule that still
     * says *ask me*. Nothing was read and nobody refused, and *not permitted*
     * with `may_ask_again` true is the honest reading of that — the screen
     * offers another go, which is exactly right for a question still open.
     */
    private fun refused(settled: CameraRule): Map<String, Any> {
        val why = settled.whyNot ?: WhyNothingWasRead.THE_CAMERA_IS_NOT_PERMITTED

        Log.d(TAG, "scanning: nothing read, ${why.word}")

        return Envelope.refusing(NOTHING, why.word, settled.mayAskAgain).asAnswer()
    }

    /**
     * The answer once the scanner has closed.
     *
     * Empty and absent mean the same thing here — the screen went away without
     * a code — because the queue carries a string and has no third value to put
     * in it. A code that was read is never empty: a QR with no content is not
     * something a camera reports.
     */
    private fun whatWasRead(read: String?): Map<String, Any> {
        if (read.isNullOrEmpty()) {
            Log.d(TAG, "scanning: nothing read, the scanner closed")

            return Envelope
                .refusing(NOTHING, WhyNothingWasRead.THE_OPERATOR_CLOSED_IT.word, true)
                .asAnswer()
        }

        Log.d(TAG, "scanning: a code was read")

        return Envelope.of(READ, mapOf("payload" to read)).asAnswer()
    }
}
