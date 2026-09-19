package app.lemonfiber.native

import android.graphics.Color
import android.os.Bundle
import android.view.Gravity
import android.view.ViewGroup
import android.widget.FrameLayout
import android.widget.TextView
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage
import java.util.concurrent.atomic.AtomicBoolean

/**
 * The camera, on screen, reading one pairing code.
 *
 * A screen of its own because that is what a camera is on this platform: it
 * takes the display, and the operator is either in it or they are not. It is
 * finished by the first code it reads or by the back gesture, and either way
 * [ScanningFunctions.finished] is what the waiting bridge call is listening to.
 *
 * **One code, then gone.** There is no continuous mode and no way to ask for
 * one. A scanner left open is a camera left on, and the one thing this
 * application reads is a pairing code that is spent the moment it is used.
 *
 * **Nothing read reaches a log line.** The only value that passes through here
 * is the one being protected, so the log says that something was read and never
 * what. That is a promise a reader can check by reading this file, which is the
 * only kind of promise worth making about a camera.
 *
 * Built out of views rather than composed, deliberately. The preview and one
 * line of text is the whole screen, and a Compose dependency for that would put
 * a rendering toolkit into a plugin whose job is to answer a question.
 *
 * A [FragmentActivity] rather than an `AppCompatActivity`, for the same kind of
 * reason: it is what the rest of this plugin already needs on the classpath, it
 * is the lifecycle owner CameraX binds to, and it carries no dependency on a
 * support theme. The theme it is declared with is the platform's own.
 */
public class ScanningActivity : FragmentActivity() {
    /**
     * Whether something has already been handed back.
     *
     * The analyser delivers frames on its own thread and the back gesture
     * arrives on the main one, so both can reach the end at once. Without this,
     * a code read at the moment somebody presses back would answer twice — and
     * the second answer would be *the operator closed it*, about a scan that
     * succeeded.
     */
    private val done = AtomicBoolean(false)

    /** What the operator is told the camera is for. */
    private lateinit var said: String

    override fun onCreate(state: Bundle?) {
        super.onCreate(state)

        said = intent.getStringExtra(PROMPT) ?: ""

        val preview = PreviewView(this)
        val frame = FrameLayout(this)

        frame.addView(preview, ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.MATCH_PARENT)
        frame.addView(caption(), captioned())
        setContentView(frame)

        open(preview)
    }

    /**
     * Hand back whatever this scan came to, once.
     *
     * Called from the analyser's thread and from the main one, which is why the
     * guard is a compare-and-set rather than a boolean somebody reads and then
     * writes.
     */
    private fun answer(payload: String?) {
        if (!done.compareAndSet(false, true)) {
            return
        }

        ScanningFunctions.finished(payload)
        finish()
    }

    /**
     * Closed without a code, whichever way it was closed.
     *
     * `onDestroy` rather than a back-gesture callback, because the ways out of
     * a screen are not all gestures: the platform can take it for memory, and
     * an operator can swipe the whole task away. Every one of those is the
     * scanner closing with nothing read, and the call waiting on the other side
     * has to be told about all of them or it waits out its ten minutes.
     */
    override fun onDestroy() {
        super.onDestroy()
        answer(null)
    }

    /** The sentence painted over the preview. */
    private fun caption(): TextView {
        val line = TextView(this)

        line.text = said
        line.setTextColor(Color.WHITE)
        line.setBackgroundColor(CAPTION_BACKGROUND)
        line.setPadding(PADDING, PADDING, PADDING, PADDING)
        line.gravity = Gravity.CENTER

        return line
    }

    /** Where that sentence sits. */
    private fun captioned(): FrameLayout.LayoutParams =
        FrameLayout.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.WRAP_CONTENT,
            Gravity.BOTTOM,
        )

    /** Start the camera and point the reader at what it sees. */
    private fun open(preview: PreviewView) {
        val cameras = ProcessCameraProvider.getInstance(this)

        cameras.addListener({
            val provider = cameras.get()
            val feed = Preview.Builder().build()
            val frames =
                ImageAnalysis
                    .Builder()
                    .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                    .build()

            feed.surfaceProvider = preview.surfaceProvider
            frames.setAnalyzer(ContextCompat.getMainExecutor(this), ::read)

            provider.unbindAll()
            provider.bindToLifecycle(this, CameraSelector.DEFAULT_BACK_CAMERA, feed, frames)
        }, ContextCompat.getMainExecutor(this))
    }

    /**
     * One frame, looked at for a QR code.
     *
     * Only QR, because that is what lemonfiber's pairing material travels in:
     * the payload is a JSON object carrying an address and a certificate
     * digest, and seven of the eight formats a reader can be asked for cannot
     * hold one. A reader that accepted them all would answer with whatever else
     * happened to be in shot — a product code on a desk beside the stack is not
     * pairing material, and reporting it would dismiss the scanner and then
     * report a code that would not parse.
     */
    private fun read(frame: ImageProxy) {
        val image = frame.image

        if (image == null || done.get()) {
            frame.close()

            return
        }

        val reader = BarcodeScanning.getClient()

        reader
            .process(InputImage.fromMediaImage(image, frame.imageInfo.rotationDegrees))
            .addOnSuccessListener { found -> found.firstNotNullOfOrNull(::pairingMaterialIn)?.let(::answer) }
            .addOnCompleteListener { frame.close() }
    }

    /** What one barcode carries, where it is the kind this application reads. */
    private fun pairingMaterialIn(barcode: Barcode): String? =
        if (barcode.format == Barcode.FORMAT_QR_CODE) barcode.rawValue else null

    /** Where the prompt's key and the screen's own measurements are named. */
    public companion object {
        /** The extra the caller's own sentence arrives under. */
        public const val PROMPT: String = "prompt"

        /** Black at two-thirds, so a light stack screen behind it stays readable. */
        private const val CAPTION_BACKGROUND = 0xAA000000.toInt()

        /** The breathing room around the sentence, in pixels. */
        private const val PADDING = 48
    }
}
