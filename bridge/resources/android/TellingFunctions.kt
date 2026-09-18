package app.lemonfiber.native

import android.Manifest
import android.app.Activity
import android.app.AlarmManager
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.SharedPreferences
import android.os.Build
import android.os.Bundle
import android.util.Log
import androidx.core.app.ActivityCompat
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * Telling somebody something, on Android.
 *
 * Namespace: `Lemonfiber.Telling.*`
 *
 * Local notifications, never pushed. A pushed payload travels through Google's
 * relay to reach this handset, which is a third party reading what a stack said
 * about somebody's home; a local notification is composed here, displayed here,
 * and never leaves the device.
 *
 * The decisions worth testing are not in this file. [NotificationRule] holds
 * what the four facts a platform reports mean together, and [Recurrence] holds
 * when a repeat next comes round; both run on a JVM with no device in sight.
 * What is here is the part that cannot be tested off a handset: reading the
 * platform's facts, posting a notification, arming an alarm.
 *
 * **Nothing rendered is logged.** The title and the body are the only place a
 * stack's name could reach a log line, so the log carries the identifier the
 * caller chose and the outcome word and nothing else. Both of those are closed
 * words rather than rendered text, which is what makes the promise checkable by
 * reading the file.
 */
public object TellingFunctions {
    /** What this plugin's log lines are tagged with. */
    private const val TAG = "Lemonfiber"

    /** Where the record of what this application has asked for is kept. */
    private const val STORE = "lemonfiber.telling"

    /** Whether the notification prompt has ever been raised by this application. */
    private const val EVER_ASKED = "ever_asked"

    /** The identifiers this application has an alarm armed for. */
    private const val ARMED = "armed"

    /** The channel every notification this application posts belongs to. */
    private const val CHANNEL = "lemonfiber.alerts"

    /** What an alarm from this plugin is recognised by. */
    private const val WHEN_IT_IS_TIME = "app.lemonfiber.native.TELL"

    /** The code the permission result is reported back under. */
    private const val ASKING = 0x1EA0

    /** Milliseconds to the second, where the wire counts in seconds. */
    private const val MILLISECONDS = 1_000L

    /**
     * What the platform reports, gathered into the four facts the rule reads.
     *
     * The version check is here rather than inside the rule on purpose: which
     * Android this is, is a fact about the platform, and the rule is the part
     * that has to be runnable without one.
     *
     * `areNotificationsEnabled()` is one call covering three switches. From
     * Android 33 it is false while `POST_NOTIFICATIONS` is not granted, and on
     * every version it is false where the operator has switched the
     * application off in settings — which is the same answer to a caller with
     * something to show, and the reason the rule takes one fact rather than
     * three.
     */
    private fun rule(activity: Activity): NotificationRule =
        NotificationRule(
            wouldAppear = NotificationManagerCompat.from(activity).areNotificationsEnabled(),
            permissionIsAsked = Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU,
            wouldExplain =
                ActivityCompat.shouldShowRequestPermissionRationale(
                    activity,
                    Manifest.permission.POST_NOTIFICATIONS,
                ),
            everAsked = settings(activity).getBoolean(EVER_ASKED, false),
        )

    /** Where this plugin keeps what only it can know. */
    private fun settings(context: Context): SharedPreferences =
        context.getSharedPreferences(STORE, Context.MODE_PRIVATE)

    /**
     * The platform's notification service, which several calls here need.
     *
     * A property rather than a method because that is what it is — the service
     * a context has, not something this object computes.
     */
    private val Context.notifications: NotificationManager
        get() = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

    /**
     * The channel every notification here belongs to, created if it is not there.
     *
     * Answers false where the operator has switched that channel off, which is
     * a different refusal from switching the application off: one is answered
     * by sending them to the channel's own settings and the other to the
     * application's.
     *
     * The channel is named after the application, read from the platform rather
     * than written here — the label is already translated into whatever the
     * handset is set to, and a string in this file would be English on a Dutch
     * phone.
     */
    private fun channelIsOpen(context: Context): Boolean {
        val service = context.notifications

        if (service.getNotificationChannel(CHANNEL) == null) {
            service.createNotificationChannel(
                NotificationChannel(
                    CHANNEL,
                    context.applicationInfo.loadLabel(context.packageManager),
                    NotificationManager.IMPORTANCE_DEFAULT,
                ),
            )
        }

        return service.getNotificationChannel(CHANNEL)?.importance != NotificationManager.IMPORTANCE_NONE
    }

    /**
     * One identifier, as a number the platform can key an alarm and a notification by.
     *
     * The sign is taken off because a negative request code is refused by
     * `PendingIntent`.
     */
    private fun codeFor(id: String): Int = id.hashCode() and Int.MAX_VALUE

    /** Post one notification now, or say why it was not posted. */
    private fun post(
        context: Context,
        said: WhatToSay,
    ): Map<String, Any> =
        try {
            context.notifications.notify(
                codeFor(said.id),
                NotificationCompat.Builder(context, CHANNEL)
                    .setContentTitle(said.title)
                    .setContentText(said.body)
                    .setSmallIcon(context.applicationInfo.icon)
                    .setAutoCancel(true)
                    .setPriority(NotificationCompat.PRIORITY_DEFAULT)
                    .build(),
            )

            Log.d(TAG, "telling ${said.id}: shown")
            Envelope.of("shown").asAnswer()
        } catch (refused: SecurityException) {
            // The exception's own message is dropped rather than logged. A
            // platform exception carries what it failed on, and what it failed
            // on here is the rendered sentence.
            Log.w(TAG, "telling ${said.id}: the device refused it (${refused.javaClass.simpleName})")
            Envelope.refusing("withheld", because = "the_device_refused").asAnswer()
        }

    /** The alarm that will post one notification later. */
    private fun pendingFor(
        context: Context,
        said: WhatToSay,
        repeat: Bundle,
    ): PendingIntent =
        PendingIntent.getBroadcast(
            context,
            codeFor(said.id),
            Intent(context, WhenTheTimeComes::class.java)
                .setAction(WHEN_IT_IS_TIME)
                .putExtra("id", said.id)
                .putExtra("title", said.title)
                .putExtra("body", said.body)
                .putExtras(repeat),
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )

    /**
     * Arm an alarm, and remember that it is armed.
     *
     * Exact where the platform allows it and approximate where it does not.
     * `USE_EXACT_ALARM` is the permission that would make it always exact, and
     * it is one a store reviews and grants to applications whose whole purpose
     * is an alarm clock. This is not one of those, so a notification that may
     * arrive a few minutes late is the right trade and is made here rather than
     * left to whatever the device happens to allow.
     */
    private fun arm(
        context: Context,
        said: WhatToSay,
        at: Long,
        repeat: Bundle,
    ): Map<String, Any> {
        val alarms = context.getSystemService(Context.ALARM_SERVICE) as AlarmManager
        val intent = pendingFor(context, said, repeat)

        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S || alarms.canScheduleExactAlarms()) {
            alarms.setExactAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, at, intent)
        } else {
            alarms.set(AlarmManager.RTC_WAKEUP, at, intent)
        }

        remember(context, said.id, armed = true)
        Log.d(TAG, "telling ${said.id}: scheduled")

        return Envelope.of("scheduled").asAnswer()
    }

    /** Add an identifier to the armed set, or take it out of one. */
    private fun remember(
        context: Context,
        id: String,
        armed: Boolean,
    ) {
        val held = armedNow(context).toMutableSet()

        if (armed) held.add(id) else held.remove(id)

        settings(context).edit().putStringSet(ARMED, held).apply()
    }

    /** Every identifier this application has an alarm armed for. */
    private fun armedNow(context: Context): Set<String> =
        settings(context).getStringSet(ARMED, emptySet()) ?: emptySet()

    /** Take one notification away, whether it is showing, armed or neither. */
    private fun withdraw(
        context: Context,
        id: String,
    ) {
        (context.getSystemService(Context.ALARM_SERVICE) as AlarmManager)
            .cancel(pendingFor(context, WhatToSay(id, "", ""), Bundle.EMPTY))

        context.notifications.cancel(codeFor(id))
        remember(context, id, armed = false)
    }

    /**
     * `Lemonfiber.Telling.Standing` — what the operator has already said.
     *
     * Reads and never prompts, which is the whole reason it is a separate
     * function from [Ask]: an application that cannot read the standing answer
     * without raising a dialog has no way to obey one, because reading becomes
     * asking and somebody who already refused gets asked again.
     */
    public class Standing(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            Envelope.of(rule(activity).said.word).asAnswer()
    }

    /**
     * `Lemonfiber.Telling.Ask` — raise the prompt, and record that it was raised.
     *
     * The recording is the part nothing else can do. Android reports *never
     * asked* and *refused for good* identically, so the only way to tell them
     * apart afterwards is to have written down that the dialog went up.
     *
     * It waits for the answer rather than returning while the dialog is still
     * on screen. Returning early would mean reading that record back before the
     * operator had answered — which says *refused* about somebody who is
     * looking at the question. The wait ends when this activity is resumed,
     * which is what happens when the platform's own dialog goes away, and it
     * gives up after two minutes rather than holding the caller for ever.
     */
    public class Ask(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            if (!rule(activity).mayAsk) {
                return Envelope.of(rule(activity).said.word).asAnswer()
            }

            settings(activity).edit().putBoolean(EVER_ASKED, true).apply()

            askFor(activity, Manifest.permission.POST_NOTIFICATIONS, ASKING)

            Log.d(TAG, "telling: the prompt was raised")

            return Envelope.of(rule(activity).said.word).asAnswer()
        }
    }

    /** `Lemonfiber.Telling.Show` — put something in front of the operator now. */
    public class Show(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val said = WhatToSay.from(parameters)

            return when {
                !rule(activity).mayShow -> Envelope.refusing("withheld", "not_permitted").asAnswer()
                !channelIsOpen(activity) -> Envelope.refusing("withheld", "no_such_channel").asAnswer()
                else -> post(activity, said)
            }
        }
    }

    /** `Lemonfiber.Telling.Schedule` — put something in front of them at a time. */
    public class Schedule(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val said = WhatToSay.from(parameters)
            val at =
                (parameters["at"] as? Number)?.toLong()
                    ?: throw BridgeError.InvalidParameters("at is required")

            return when {
                !rule(activity).mayShow -> Envelope.refusing("withheld", "not_permitted").asAnswer()
                !channelIsOpen(activity) -> Envelope.refusing("withheld", "no_such_channel").asAnswer()
                at * MILLISECONDS <= System.currentTimeMillis() ->
                    Envelope.refusing("withheld", "the_time_has_passed").asAnswer()

                else -> arm(activity, said, at * MILLISECONDS, Bundle.EMPTY)
            }
        }
    }

    /** `Lemonfiber.Telling.ScheduleRecurring` — the same, over and over. */
    public class ScheduleRecurring(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val said = WhatToSay.from(parameters)
            val repeat = asked(parameters)
            val next = repeat?.nextAfter(System.currentTimeMillis())

            return when {
                !rule(activity).mayShow -> Envelope.refusing("withheld", "not_permitted").asAnswer()
                !channelIsOpen(activity) -> Envelope.refusing("withheld", "no_such_channel").asAnswer()
                repeat == null || next == null -> Envelope.refusing("withheld", "no_such_repeat").asAnswer()
                else -> arm(activity, said, next, repeat.asExtras())
            }
        }

        /** The recurrence the caller asked for, or nothing where it named none. */
        private fun asked(parameters: Map<String, Any>): Recurrence? =
            HowOften.saying(parameters["frequency"] as? String)?.let { frequency ->
                Recurrence(
                    frequency = frequency,
                    hour = (parameters["hour"] as? Number)?.toInt() ?: 0,
                    minute = (parameters["minute"] as? Number)?.toInt() ?: 0,
                    weekday = (parameters["weekday"] as? Number)?.toInt() ?: 0,
                    dayOfMonth = (parameters["dayOfMonth"] as? Number)?.toInt() ?: 1,
                    month = (parameters["month"] as? Number)?.toInt() ?: 1,
                )
            }
    }

    /**
     * `Lemonfiber.Telling.Cancel` — take one back.
     *
     * Cancelling something that was never scheduled answers `cancelled`, for
     * the reason forgetting a key that was never kept answers `forgotten`: it
     * is the ordinary case after a refusal, and getting rid of something is the
     * one operation that must always work.
     */
    public class Cancel(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            withdraw(activity, WhatToSay.identifierIn(parameters))

            return Envelope.of("cancelled").asAnswer()
        }
    }

    /** `Lemonfiber.Telling.CancelAll` — take back everything this application armed. */
    public class CancelAll(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            armedNow(activity).forEach { withdraw(activity, it) }
            activity.notifications.cancelAll()

            return Envelope.of("cancelled").asAnswer()
        }
    }

    /**
     * `Lemonfiber.Telling.Pending` — what is still to come.
     *
     * Read from this plugin's own record rather than from the platform, which
     * keeps none: `AlarmManager` can be asked to arm one and cannot be asked
     * what it holds.
     */
    public class Pending(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            Envelope.of("read", carrying = mapOf("pending" to armedNow(activity).toList())).asAnswer()
    }

    /**
     * `Lemonfiber.Telling.ClearBadge` — take the count off the launcher icon.
     *
     * Android has no badge of its own: what a launcher draws is a count of the
     * notifications this application is showing. So clearing the badge is
     * clearing those, and there is nothing narrower to do.
     */
    public class ClearBadge(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            activity.notifications.cancelAll()

            return Envelope.of("cleared").asAnswer()
        }
    }

    /**
     * What a scheduled notification is posted by when its time arrives.
     *
     * Declared in the plugin's manifest so that the platform can reach it while
     * this application is not running, which is the whole point of scheduling
     * one.
     *
     * A repeat re-arms itself here rather than being registered as a repeating
     * alarm. `setRepeating` is inexact on every version that matters and
     * carries no way to say *this time of day in this time zone*, so a repeat
     * that crossed a daylight-saving boundary would drift by an hour and stay
     * there.
     */
    public class WhenTheTimeComes : BroadcastReceiver() {
        override fun onReceive(
            context: Context,
            intent: Intent,
        ) {
            val said =
                WhatToSay(
                    id = intent.getStringExtra("id") ?: return,
                    title = intent.getStringExtra("title").orEmpty(),
                    body = intent.getStringExtra("body").orEmpty(),
                )

            channelIsOpen(context)
            post(context, said)

            val repeat = again(intent)
            val next = repeat?.nextAfter(System.currentTimeMillis())

            if (repeat == null || next == null) {
                remember(context, said.id, armed = false)
            } else {
                arm(context, said, next, repeat.asExtras())
            }
        }

        /** The recurrence this alarm carried, or nothing where it was a one-off. */
        private fun again(intent: Intent): Recurrence? =
            HowOften.saying(intent.getStringExtra("frequency"))?.let { frequency ->
                Recurrence(
                    frequency = frequency,
                    hour = intent.getIntExtra("hour", 0),
                    minute = intent.getIntExtra("minute", 0),
                    weekday = intent.getIntExtra("weekday", 0),
                    dayOfMonth = intent.getIntExtra("dayOfMonth", 1),
                    month = intent.getIntExtra("month", 1),
                )
            }
    }
}

/**
 * What one notification says, as this bridge is willing to carry it.
 *
 * Three fields and no more. The vendor's own takes a sound, a badge, a
 * subtitle, an arbitrary data payload and up to three action buttons; none of
 * that has a caller here, and every one of them is another place a value could
 * travel that the application never meant to send.
 */
public data class WhatToSay(
    /** What this notification is about, chosen by the caller and never rendered. */
    public val id: String,
    /** The line the operator reads first. */
    public val title: String,
    /** The line under it. */
    public val body: String,
) {
    /** Where a notification is read out of what the bridge was handed. */
    public companion object {
        /**
         * The three fields, or a refusal naming all of them at once.
         *
         * One guard rather than three, because a caller handed "id is required"
         * learns as much from "id, title and body are required" and the reading
         * stays short enough to check at a glance.
         */
        public fun from(parameters: Map<String, Any>): WhatToSay {
            val id = parameters["id"] as? String
            val title = parameters["title"] as? String
            val body = parameters["body"] as? String

            if (id == null || title == null || body == null) {
                throw BridgeError.InvalidParameters("id, title and body are required")
            }

            return WhatToSay(id, title, body)
        }

        /** The identifier alone, for the calls that carry nothing else. */
        public fun identifierIn(parameters: Map<String, Any>): String =
            parameters["id"] as? String ?: throw BridgeError.InvalidParameters("id is required")
    }
}

/**
 * A recurrence, as an alarm can carry it and hand it back.
 *
 * An extension rather than a method, so that [Recurrence] itself stays free of
 * anything Android: it is a rule, it is unit-tested, and the harness that runs
 * it has no `Bundle` on its classpath.
 */
public fun Recurrence.asExtras(): Bundle =
    Bundle().apply {
        putString("frequency", frequency.word)
        putInt("hour", hour)
        putInt("minute", minute)
        putInt("weekday", weekday)
        putInt("dayOfMonth", dayOfMonth)
        putInt("month", month)
    }
