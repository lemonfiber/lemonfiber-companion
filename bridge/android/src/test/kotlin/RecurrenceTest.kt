package app.lemonfiber.native

import java.time.DayOfWeek
import java.time.Duration
import java.time.Instant
import java.time.ZoneId
import java.time.ZonedDateTime
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * When a repeating notification next comes round.
 *
 * The same nine cases as `RecurrenceTests.swift`, in the same order. The two
 * platforms compute this with different standard libraries on purpose, which is
 * exactly why they are asked the same questions: a difference in the answer is
 * the thing worth catching, and a difference in the idiom is not.
 *
 * Every assertion is about the answer's fields and its distance from where it
 * counted, never about an absolute moment. A test that named one would pass in
 * one time zone and fail in the next.
 */
class RecurrenceTest {
    private val zone: ZoneId = ZoneId.systemDefault()

    private val halfPastTwelve: ZonedDateTime = ZonedDateTime.of(2026, 3, 15, 12, 30, 0, 0, zone)

    private val daily: Recurrence =
        Recurrence(
            frequency = HowOften.DAILY,
            hour = 9,
            minute = 0,
            weekday = 0,
            dayOfMonth = 15,
            month = 12,
        )

    private fun next(repeat: Recurrence): ZonedDateTime =
        Instant
            .ofEpochMilli(
                repeat.nextAfter(halfPastTwelve.toInstant().toEpochMilli())
                    ?: error("this recurrence answered no next occurrence"),
            )
            .atZone(zone)

    private fun nothing(repeat: Recurrence): Long? =
        repeat.nextAfter(halfPastTwelve.toInstant().toEpochMilli())

    @Test
    fun `an hourly repeat lands on the minute asked for`() {
        val landed = next(daily.copy(frequency = HowOften.HOURLY, minute = 45))

        assertEquals(45, landed.minute)
        assertTrue(landed.isAfter(halfPastTwelve))
        assertTrue(Duration.between(halfPastTwelve, landed) <= Duration.ofHours(1))
    }

    @Test
    fun `a daily repeat lands at the hour asked for`() {
        val landed = next(daily.copy(hour = 21))

        assertEquals(21, landed.hour)
        assertEquals(0, landed.minute)
        assertTrue(landed.isAfter(halfPastTwelve))
        assertTrue(Duration.between(halfPastTwelve, landed) <= Duration.ofHours(24))
    }

    @Test
    fun `a weekly repeat lands on the day asked for`() {
        // Sunday, which the wire counts as zero and the two calendars count
        // differently again. An off-by-one here is an alert arriving on the
        // wrong day every week, with nothing on any screen to say so.
        val landed = next(daily.copy(frequency = HowOften.WEEKLY, weekday = 0))

        assertEquals(DayOfWeek.SUNDAY, landed.dayOfWeek)
        assertTrue(landed.isAfter(halfPastTwelve))
        assertTrue(Duration.between(halfPastTwelve, landed) <= Duration.ofDays(7))
    }

    @Test
    fun `a monthly repeat lands on the date asked for`() {
        val landed = next(daily.copy(frequency = HowOften.MONTHLY, dayOfMonth = 20))

        assertEquals(20, landed.dayOfMonth)
        assertEquals(9, landed.hour)
        assertTrue(landed.isAfter(halfPastTwelve))
    }

    @Test
    fun `a yearly repeat lands in the month asked for`() {
        val landed = next(daily.copy(frequency = HowOften.YEARLY, dayOfMonth = 25, month = 12))

        assertEquals(12, landed.monthValue)
        assertEquals(25, landed.dayOfMonth)
        assertTrue(landed.isAfter(halfPastTwelve))
    }

    @Test
    fun `a repeat whose moment has been today is tomorrow's`() {
        // The one way this can be wrong, and the reason it is a rule rather
        // than three lines in the shim. An alarm armed for a moment that has
        // already passed fires the instant it is set, and then again every
        // period, for as long as the application is installed.
        val landed = next(daily.copy(hour = 12, minute = 0))

        assertTrue(landed.isAfter(halfPastTwelve))
        assertEquals(12, landed.hour)
        assertTrue(Duration.between(halfPastTwelve, landed) >= Duration.ofHours(23))
    }

    @Test
    fun `a repeat nothing could satisfy comes round never`() {
        // Null rather than a moment, and a moment is the trap: a calendar asked
        // for the thirty-first of a thirty-day month answers whatever it can
        // reach instead, and the two platforms reach different things. Refusing
        // is the only answer both can give, so it is the one both give.
        assertNull(nothing(daily.copy(hour = 24)))
        assertNull(nothing(daily.copy(frequency = HowOften.HOURLY, minute = 60)))
        assertNull(nothing(daily.copy(frequency = HowOften.WEEKLY, weekday = 7)))
        assertNull(nothing(daily.copy(frequency = HowOften.MONTHLY, dayOfMonth = 31)))
        assertNull(nothing(daily.copy(frequency = HowOften.YEARLY, month = 13)))
    }

    @Test
    fun `each frequency fixes the fields it names and no others`() {
        // What a frequency actually means, asked directly rather than through
        // the arithmetic. Both shims read this — one walks a date onto the
        // fields, the other hands them to a repeating trigger — so a field
        // fixed on one platform and left open on the other is a repeat that
        // arrives at a different moment on each, with nothing to say so.
        assertEquals(WhatItFixes(null, 0, null, null, null), daily.copy(frequency = HowOften.HOURLY).fixes())
        assertEquals(WhatItFixes(9, 0, null, null, null), daily.fixes())
        assertEquals(WhatItFixes(9, 0, 0, null, null), daily.copy(frequency = HowOften.WEEKLY).fixes())
        assertEquals(WhatItFixes(9, 0, null, 15, null), daily.copy(frequency = HowOften.MONTHLY).fixes())
        assertEquals(WhatItFixes(9, 0, null, 15, 12), daily.copy(frequency = HowOften.YEARLY).fixes())
    }

    @Test
    fun `a frequency nothing recognises is not one`() {
        // Refused rather than defaulted. A word this does not know is a caller
        // asking for something that will not happen, and arming a daily repeat
        // instead reports success for an alert nobody chose.
        assertEquals(HowOften.DAILY, HowOften.saying("daily"))
        assertNull(HowOften.saying("fortnightly"))
        assertNull(HowOften.saying(null))
        assertEquals(
            listOf("hourly", "daily", "weekly", "monthly", "yearly"),
            HowOften.entries.map { it.word },
        )
    }
}
