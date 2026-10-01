package app.lemonfiber.native

import android.app.Activity
import android.util.TypedValue
import android.view.View
import android.view.ViewGroup
import java.lang.ref.WeakReference

/**
 * The cover the lock puts over the window while the glass must show nothing.
 *
 * [TheLock] decides when; this is only how. Main thread only.
 */
internal object TheCover {
    /** The cover, while it is up. */
    private var cover: View? = null

    /** The activity the cover is on. */
    private var coveredOn: WeakReference<Activity>? = null

    /**
     * Put the cover up or take it down, as the rule says.
     *
     * Opaque and empty, in the window's own background colour, and the content
     * under it is taken out of the screen reader's reach while it is up: a
     * cover a screen reader can read through covers nothing.
     */
    internal fun show(
        activity: Activity,
        mustCover: Boolean,
    ) {
        activity.runOnUiThread {
            if (mustCover) {
                raise(activity)
            } else {
                lift()
            }
        }
    }

    /** Put the cover over [activity], taking it off any other first. */
    private fun raise(activity: Activity) {
        if (coveredOn?.get() === activity && cover != null) {
            return
        }

        lift()

        val over =
            View(activity).apply {
                setBackgroundColor(background(activity))
                isClickable = true
                importantForAccessibility = View.IMPORTANT_FOR_ACCESSIBILITY_NO
            }

        (activity.window.decorView as ViewGroup).addView(
            over,
            ViewGroup.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.MATCH_PARENT),
        )
        activity.findViewById<View>(android.R.id.content)?.importantForAccessibility =
            View.IMPORTANT_FOR_ACCESSIBILITY_NO_HIDE_DESCENDANTS

        cover = over
        coveredOn = WeakReference(activity)
    }

    /** Take the cover down, wherever it is. */
    private fun lift() {
        val on = coveredOn?.get()

        cover?.let { (it.parent as? ViewGroup)?.removeView(it) }
        on?.findViewById<View>(android.R.id.content)?.importantForAccessibility =
            View.IMPORTANT_FOR_ACCESSIBILITY_AUTO

        cover = null
        coveredOn = null
    }

    /** The window's own background colour, so the cover is dark in dark mode. */
    private fun background(activity: Activity): Int {
        val value = TypedValue()
        activity.theme.resolveAttribute(android.R.attr.colorBackground, value, true)

        return value.data
    }
}
