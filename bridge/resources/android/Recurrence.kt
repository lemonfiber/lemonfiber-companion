package app.lemonfiber.native

import java.time.DayOfWeek
import java.time.Instant
import java.time.ZoneId
import java.time.ZonedDateTime
import java.time.temporal.ChronoUnit

/**
 * How often a repeating notification comes round.
 *
 * A closed set, because an unrecognised word is a caller asking for something
 * that will never happen. Reading it as a default — daily, usually — arms a
 * repeat nobody asked for and reports success, which is worse than refusing.
 */
public enum class HowOften(
    /** What this frequency is called on the wire. */
    public val word: String,
    /** How much of the calendar one whole period of it moves. */
    public val period: ChronoUnit,
) {
    /** Every hour, on a minute. */
    HOURLY("hourly", ChronoUnit.HOURS),

    /** Every day, at an hour and a minute. */
    DAILY("daily", ChronoUnit.DAYS),

    /** Every week, on a day. */
    WEEKLY("weekly", ChronoUnit.WEEKS),

    /** Every month, on a date. */
    MONTHLY("monthly", ChronoUnit.MONTHS),

    /** Every year, on a date in a month. */
    YEARLY("yearly", ChronoUnit.YEARS),
    ;

    /** Where a word becomes one of the five, or nothing. */
    public companion object {
        /**
         * The frequency that word names, or nothing where it names none.
         *
         * Null rather than a default. A frequency this does not recognise is a
         * caller asking for something that will not happen, and quietly arming
         * a different one is how an operator comes to be told about a backup at
         * an hour nobody chose.
         */
        public fun saying(word: String?): HowOften? = entries.firstOrNull { it.word == word }
    }
}

/**
 * The fields a frequency fixes, and the ones it leaves to the calendar.
 *
 * What makes a frequency mean anything: a daily repeat names an hour and a
 * minute and lets every day match, and a yearly one names a month and a day as
 * well. A field left unset is one the calendar is free to fill.
 *
 * Counted the way the wire counts, which is the way both platforms' own
 * notification APIs do — months from one, weekdays from Sunday as zero. Each
 * platform's shim converts to whatever its calendar wants, in one place, where
 * an off-by-one is visible.
 *
 * Deliberately mirrors `WhatItFixes` in `Recurrence.swift`.
 */
public data class WhatItFixes(
    /** The hour of the day, where the frequency names one. */
    public val hour: Int?,
    /** The minute of the hour, which every frequency names. */
    public val minute: Int?,
    /** The day of the week, counted from Sunday as zero. */
    public val weekday: Int?,
    /** The day of the month, counted from one. */
    public val dayOfMonth: Int?,
    /** The month, counted from one. */
    public val month: Int?,
)

/**
 * When a repeating notification next comes round.
 *
 * Arithmetic, which is why it is here rather than in the shim: the one way this
 * can be wrong is to answer a moment that has already been, and an alarm armed
 * for the past fires the instant it is set and then again every period forever.
 * That is a decision, it is testable without a handset, and a handset is the
 * worst place to find out about it.
 *
 * **The two platforms do not compute it the same way, deliberately.** Here it
 * is `java.time`, which is what a Kotlin reader expects and what Android has
 * had since the version this plugin needs anyway; on iOS it is
 * `Calendar.nextDate(after:matching:)`, which is what a Swift reader expects
 * and which answers the same question in one call. Mirroring the *cases* is
 * what these paired files are for; mirroring an idiom across two standard
 * libraries would make one half read like a translation of the other.
 *
 * What is mirrored exactly is the surface and the answer. Both halves expose
 * [nextAfter] and [fixes] and nothing else, and both answer the awkward case
 * the same way: a recurrence naming something no calendar has comes round
 * never. Each shim uses whichever of the two its platform needs — Android arms
 * one moment at a time and re-arms itself, iOS hands the fields to a repeating
 * trigger and is not woken again — and neither rule knows which.
 */
public data class Recurrence(
    /** How often it comes round. */
    public val frequency: HowOften,
    /** The hour of the day it is wanted at, ignored for an hourly repeat. */
    public val hour: Int,
    /** The minute of that hour. */
    public val minute: Int,
    /** The day of the week, counted from Sunday, for a weekly repeat. */
    public val weekday: Int,
    /** The day of the month, for a monthly or yearly repeat. */
    public val dayOfMonth: Int,
    /** The month, counted from one, for a yearly repeat. */
    public val month: Int,
) {
    /**
     * The next moment this comes round, or nothing where no moment could.
     *
     * Strictly after the instant given, and that is the whole of it. The branch
     * below fixes the fields the frequency names and leaves the rest where they
     * are, which lands either just ahead of the instant or just behind it;
     * where it lands behind, one whole period is added.
     *
     * Null where the recurrence names something a calendar does not have — an
     * hour of 24, a month of 13, a day of the month past the 28th. The shim
     * reads that as a refusal rather than arming an alarm, because a repeat
     * nobody can predict is worse than one that was declined.
     *
     * @param instant the moment to count from, in milliseconds since the epoch.
     * @return the next occurrence, in milliseconds since the epoch, or null.
     */
    public fun nextAfter(instant: Long): Long? {
        if (!satisfiable) return null

        val from = Instant.ofEpochMilli(instant).atZone(ZoneId.systemDefault())
        val candidate = onOrAround(from)

        return (if (candidate.isAfter(from)) candidate else candidate.plus(1, frequency.period))
            .toInstant()
            .toEpochMilli()
    }

    /**
     * Whether every field names something a calendar has.
     *
     * **The day of the month stops at the 28th, which is a narrowing and is
     * written down here because it is one.** Every month has a 28th and no
     * month has a 31st; the two platforms' calendars disagree about what to do
     * with a 31st — `Calendar.nextDate` skips to a month that has one, and
     * `java.time` raises rather than answer — and a repeat an operator cannot
     * predict is worse than one this bridge declined to arm. Somebody wanting
     * the end of the month is asking for something neither library offers and
     * this does not pretend to.
     */
    private val satisfiable: Boolean
        get() =
            hour in 0..LAST_HOUR &&
                minute in 0..LAST_MINUTE &&
                weekday in 0..LAST_WEEKDAY &&
                dayOfMonth in 1..LAST_SAFE_DAY &&
                month in 1..LAST_MONTH

    /**
     * The fields this frequency fixes, and no others.
     *
     * The whole of what a frequency means, in one place both platforms read:
     * Android walks a date onto these fields, iOS hands them to a repeating
     * trigger, and neither has to know what `weekly` implies.
     */
    public fun fixes(): WhatItFixes =
        when (frequency) {
            HowOften.HOURLY -> WhatItFixes(null, minute, null, null, null)
            HowOften.DAILY -> WhatItFixes(hour, minute, null, null, null)
            HowOften.WEEKLY -> WhatItFixes(hour, minute, weekday, null, null)
            HowOften.MONTHLY -> WhatItFixes(hour, minute, null, dayOfMonth, null)
            HowOften.YEARLY -> WhatItFixes(hour, minute, null, dayOfMonth, month)
        }

    /**
     * The same time of day, week, month or year as this recurrence names.
     *
     * Not necessarily in the future: it is the occurrence nearest the instant
     * given, which the caller above steps forward where it has already been.
     *
     * The order the fields are applied in is load-bearing. A month is set
     * before a day of the month, from the first of the month, so that moving
     * from the thirty-first of March to February does not throw on the way
     * past; the weekday is applied last, because it is the only field that
     * moves the date rather than setting part of it.
     */
    private fun onOrAround(from: ZonedDateTime): ZonedDateTime {
        val fixed = fixes()
        var at = from.withSecond(0).withNano(0)

        fixed.minute?.let { at = at.withMinute(it) }
        fixed.hour?.let { at = at.withHour(it) }
        fixed.month?.let { at = at.withDayOfMonth(1).withMonth(it) }
        fixed.dayOfMonth?.let { at = at.withDayOfMonth(it) }
        fixed.weekday?.let { at = at.with(DayOfWeek.of(sundayIsSeven())) }

        return at
    }

    /**
     * The weekday, in the numbering `java.time` uses.
     *
     * The wire counts from Sunday as zero, because that is what both platforms'
     * notification APIs do; `DayOfWeek` counts from Monday as one and puts
     * Sunday last. Converted in one place rather than at the call site, where
     * an off-by-one is a repeat arriving on the wrong day every week and
     * nothing saying so.
     */
    private fun sundayIsSeven(): Int = if (weekday == 0) DAYS_IN_A_WEEK else weekday

    /** The edges of what a calendar will accept, named so a reader can check them. */
    private companion object {
        /** The last hour of a day, counted from zero. */
        const val LAST_HOUR = 23

        /** The last minute of an hour, counted from zero. */
        const val LAST_MINUTE = 59

        /** The last day of a week, counted from Sunday as zero. */
        const val LAST_WEEKDAY = 6

        /** The last day of the month every month has. */
        const val LAST_SAFE_DAY = 28

        /** The last month of a year, counted from one. */
        const val LAST_MONTH = 12

        /** How many days a week has, which is what Sunday becomes. */
        const val DAYS_IN_A_WEEK = 7
    }
}
