package app.lemonfiber.native

import android.app.Activity
import android.app.Application
import android.os.Bundle
import androidx.core.app.ActivityCompat
import androidx.fragment.app.FragmentActivity
import java.util.concurrent.CountDownLatch
import java.util.concurrent.TimeUnit

/** How long the prompt is waited on before its answer is read anyway. */
private const val PATIENCE_SECONDS = 120L

/**
 * Put a runtime permission prompt up, and wait for the operator to be done with it.
 *
 * **Its own file because two capabilities raise a prompt and a third will.**
 * Telling somebody and reading a code ask about different permissions and keep
 * their own record of having asked, but the awkward part is identical and it is
 * not the request — it is the wait. `requestPermissions` returns immediately,
 * so a caller that reads the platform's answer on the next line reads the state
 * from before the dialog went up. Every capability that got this wrong would get
 * it wrong the same way: reporting a refusal about somebody still looking at the
 * question.
 *
 * The waiting is bounded. An unbounded wait is a thread this application never
 * gets back if the operator walks away mid-dialog, and the answer read after the
 * limit is simply the answer as it stands — which for a prompt nobody touched is
 * the same answer it would have given anyway.
 *
 * What is *not* here is the record of having asked. That belongs to each
 * capability: the store it is kept in and the moment it is written are facts
 * about that permission, and writing it here would mean one flag standing for
 * two questions.
 */
internal fun askFor(
    activity: FragmentActivity,
    permission: String,
    code: Int,
) {
    val settled = CountDownLatch(1)
    val application = activity.application

    application.registerActivityLifecycleCallbacks(WhenTheyComeBack(application, settled))
    ActivityCompat.requestPermissions(activity, arrayOf(permission), code)
    settled.await(PATIENCE_SECONDS, TimeUnit.SECONDS)
}

/**
 * The one-shot watch that ends the wait for an answer.
 *
 * Registered as the prompt goes up and taken off again the moment this
 * application is in front of somebody once more, which is what happens when the
 * platform's own dialog is dismissed. Without taking itself off it would count
 * down on every resume for the life of the process.
 */
private class WhenTheyComeBack(
    private val application: Application,
    private val answered: CountDownLatch,
) : Application.ActivityLifecycleCallbacks {
    override fun onActivityResumed(activity: Activity) {
        application.unregisterActivityLifecycleCallbacks(this)
        answered.countDown()
    }

    override fun onActivityCreated(
        activity: Activity,
        state: Bundle?,
    ): Unit = Unit

    override fun onActivityStarted(activity: Activity): Unit = Unit

    override fun onActivityPaused(activity: Activity): Unit = Unit

    override fun onActivityStopped(activity: Activity): Unit = Unit

    override fun onActivitySaveInstanceState(
        activity: Activity,
        out: Bundle,
    ): Unit = Unit

    override fun onActivityDestroyed(activity: Activity): Unit = Unit
}
