import Foundation
import Testing

@testable import LemonfiberNative

// When a repeating notification next comes round.
//
// The same nine cases as `RecurrenceTest.kt`, in the same order. The two
// platforms compute this with different standard libraries on purpose, which is
// exactly why they are asked the same questions: a difference in the answer is
// the thing worth catching, and a difference in the idiom is not.
//
// Every assertion is about the answer's fields and its distance from where it
// counted, never about an absolute moment. A test that named one would pass in
// one time zone and fail in the next.

/// Half past twelve on a day with no month-end in it.
private func halfPastTwelve() throws -> Date {
    try #require(
        Calendar.current.date(
            from: DateComponents(year: 2026, month: 3, day: 15, hour: 12, minute: 30)
        )
    )
}

private func repeating(
    _ frequency: HowOften,
    hour: Int = 9,
    minute: Int = 0,
    weekday: Int = 0,
    dayOfMonth: Int = 15,
    month: Int = 12
) -> Recurrence {
    Recurrence(
        frequency: frequency,
        hour: hour,
        minute: minute,
        weekday: weekday,
        dayOfMonth: dayOfMonth,
        month: month
    )
}

private func part(_ component: Calendar.Component, of moment: Date) -> Int {
    Calendar.current.component(component, from: moment)
}

@Test("an hourly repeat lands on the minute asked for")
func hourlyLandsOnTheMinute() throws {
    let from = try halfPastTwelve()
    let next = try #require(repeating(.hourly, minute: 45).nextAfter(from))

    #expect(part(.minute, of: next) == 45)
    #expect(next > from)
    #expect(next.timeIntervalSince(from) <= 3600)
}

@Test("a daily repeat lands at the hour asked for")
func dailyLandsOnTheHour() throws {
    let from = try halfPastTwelve()
    let next = try #require(repeating(.daily, hour: 21).nextAfter(from))

    #expect(part(.hour, of: next) == 21)
    #expect(part(.minute, of: next) == 0)
    #expect(next > from)
    #expect(next.timeIntervalSince(from) <= 86_400)
}

@Test("a weekly repeat lands on the day asked for")
func weeklyLandsOnTheDay() throws {
    // Sunday, which the wire counts as zero and both calendars count
    // differently. An off-by-one here is an alert arriving on the wrong day
    // every week, with nothing on any screen to say so.
    let from = try halfPastTwelve()
    let next = try #require(repeating(.weekly, weekday: 0).nextAfter(from))

    #expect(part(.weekday, of: next) == 1)
    #expect(next > from)
    #expect(next.timeIntervalSince(from) <= 7 * 86_400)
}

@Test("a monthly repeat lands on the date asked for")
func monthlyLandsOnTheDate() throws {
    let from = try halfPastTwelve()
    let next = try #require(repeating(.monthly, dayOfMonth: 20).nextAfter(from))

    #expect(part(.day, of: next) == 20)
    #expect(part(.hour, of: next) == 9)
    #expect(next > from)
}

@Test("a yearly repeat lands in the month asked for")
func yearlyLandsInTheMonth() throws {
    let from = try halfPastTwelve()
    let next = try #require(repeating(.yearly, dayOfMonth: 25, month: 12).nextAfter(from))

    #expect(part(.month, of: next) == 12)
    #expect(part(.day, of: next) == 25)
    #expect(next > from)
}

@Test("a repeat whose moment has been today is tomorrow's")
func todaysMomentIsTomorrows() throws {
    // The one way this can be wrong, and the reason it is a rule rather than
    // three lines in the shim. An alarm armed for a moment that has already
    // passed fires the instant it is set, and then again every period, for as
    // long as the application is installed.
    let from = try halfPastTwelve()
    let next = try #require(repeating(.daily, hour: 12, minute: 0).nextAfter(from))

    #expect(next > from)
    #expect(part(.hour, of: next) == 12)
    #expect(next.timeIntervalSince(from) >= 23 * 3600)
}

@Test("a repeat nothing could satisfy comes round never")
func nothingCouldSatisfyIt() throws {
    // Nil rather than a moment, and the moment is the trap: a calendar asked
    // for the thirtieth of February answers whatever it can reach instead, and
    // the two platforms reach different things. Refusing is the only answer both
    // can give, so it is the one both give.
    let from = try halfPastTwelve()

    #expect(repeating(.daily, hour: 24).nextAfter(from) == nil)
    #expect(repeating(.hourly, minute: 60).nextAfter(from) == nil)
    #expect(repeating(.weekly, weekday: 7).nextAfter(from) == nil)
    #expect(repeating(.monthly, dayOfMonth: 31).nextAfter(from) == nil)
    #expect(repeating(.yearly, month: 13).nextAfter(from) == nil)
}

@Test("each frequency fixes the fields it names and no others")
func everyFrequencyFixesItsOwnFields() {
    // What a frequency actually means, asked directly rather than through the
    // arithmetic. Both shims read this — one hands the fields to a repeating
    // trigger, the other walks a date onto them — so a field fixed on one
    // platform and left open on the other is a repeat that arrives at a
    // different moment on each, with nothing to say so.
    #expect(
        repeating(.hourly).fixes()
            == WhatItFixes(hour: nil, minute: 0, weekday: nil, dayOfMonth: nil, month: nil)
    )
    #expect(
        repeating(.daily).fixes()
            == WhatItFixes(hour: 9, minute: 0, weekday: nil, dayOfMonth: nil, month: nil)
    )
    #expect(
        repeating(.weekly).fixes()
            == WhatItFixes(hour: 9, minute: 0, weekday: 0, dayOfMonth: nil, month: nil)
    )
    #expect(
        repeating(.monthly).fixes()
            == WhatItFixes(hour: 9, minute: 0, weekday: nil, dayOfMonth: 15, month: nil)
    )
    #expect(
        repeating(.yearly).fixes()
            == WhatItFixes(hour: 9, minute: 0, weekday: nil, dayOfMonth: 15, month: 12)
    )
}

@Test("a frequency nothing recognises is not one")
func anUnknownFrequencyIsNotOne() {
    // Refused rather than defaulted. A word this does not know is a caller
    // asking for something that will not happen, and arming a daily repeat
    // instead reports success for an alert nobody chose.
    #expect(HowOften.saying("daily") == .daily)
    #expect(HowOften.saying("fortnightly") == nil)
    #expect(HowOften.saying(nil) == nil)
    #expect(HowOften.allCases.map(\.word) == ["hourly", "daily", "weekly", "monthly", "yearly"])
}
