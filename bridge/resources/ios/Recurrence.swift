import Foundation

/// How often a repeating notification comes round.
///
/// A closed set, because an unrecognised word is a caller asking for something
/// that will never happen. Reading it as a default — daily, usually — arms a
/// repeat nobody asked for and reports success, which is worse than refusing.
public enum HowOften: String, CaseIterable, Sendable {
    /// Every hour, on a minute.
    case hourly

    /// Every day, at an hour and a minute.
    case daily

    /// Every week, on a day.
    case weekly

    /// Every month, on a date.
    case monthly

    /// Every year, on a date in a month.
    case yearly

    /// What this frequency is called on the wire.
    public var word: String { rawValue }

    /// The frequency that word names, or nothing where it names none.
    ///
    /// Nil rather than a default. A frequency this does not recognise is a
    /// caller asking for something that will not happen, and quietly arming a
    /// different one is how an operator comes to be told about a backup at an
    /// hour nobody chose.
    ///
    /// - Parameter word: what the caller asked for.
    /// - Returns: the frequency, or nil where the word names none.
    public static func saying(_ word: String?) -> HowOften? {
        allCases.first { $0.word == word }
    }
}

/// The fields a frequency fixes, and the ones it leaves to the calendar.
///
/// What makes a frequency mean anything: a daily repeat names an hour and a
/// minute and lets every day match, and a yearly one names a month and a day as
/// well. A field left unset is one the calendar is free to fill.
///
/// Counted the way the wire counts, which is the way both platforms' own
/// notification APIs do — months from one, weekdays from Sunday as zero. Each
/// platform's shim converts to whatever its calendar wants, in one place, where
/// an off-by-one is visible.
///
/// Deliberately mirrors `WhatItFixes` in `Recurrence.kt`.
public struct WhatItFixes: Equatable, Sendable {
    /// The hour of the day, where the frequency names one.
    public let hour: Int?

    /// The minute of the hour, which every frequency names.
    public let minute: Int?

    /// The day of the week, counted from Sunday as zero.
    public let weekday: Int?

    /// The day of the month, counted from one.
    public let dayOfMonth: Int?

    /// The month, counted from one.
    public let month: Int?

    /// - Parameters:
    ///   - hour: the hour of the day, where the frequency names one.
    ///   - minute: the minute of the hour.
    ///   - weekday: the day of the week, counted from Sunday as zero.
    ///   - dayOfMonth: the day of the month, counted from one.
    ///   - month: the month, counted from one.
    public init(hour: Int?, minute: Int?, weekday: Int?, dayOfMonth: Int?, month: Int?) {
        self.hour = hour
        self.minute = minute
        self.weekday = weekday
        self.dayOfMonth = dayOfMonth
        self.month = month
    }
}

/// When a repeating notification next comes round.
///
/// Arithmetic, which is why it is here rather than in the shim: the one way
/// this can be wrong is to answer a moment that has already been, and an alarm
/// armed for the past fires the instant it is set and then again every period
/// forever. That is a decision, it is testable without a handset, and a handset
/// is the worst place to find out about it.
///
/// **The two platforms do not compute it the same way, deliberately.** Here it
/// is `Calendar.nextDate(after:matching:)`, which is what a Swift reader
/// expects and which answers the whole question in one call; on Android it is
/// `java.time`, which is what a Kotlin reader expects. Mirroring the *cases* is
/// what these paired files are for; mirroring an idiom across two standard
/// libraries would make one half read like a translation of the other.
///
/// What is mirrored exactly is the surface and the answer. Both halves expose
/// `nextAfter` and `fixes` and nothing else, and both answer the awkward case
/// the same way: a recurrence naming something no calendar has comes round
/// never. Each shim uses whichever of the two its platform needs — Android arms
/// one moment at a time and re-arms itself, iOS hands the fields to a repeating
/// trigger and is not woken again — and neither rule knows which.
public struct Recurrence: Equatable, Sendable {
    /// How often it comes round.
    public let frequency: HowOften

    /// The hour of the day it is wanted at, ignored for an hourly repeat.
    public let hour: Int

    /// The minute of that hour.
    public let minute: Int

    /// The day of the week, counted from Sunday, for a weekly repeat.
    public let weekday: Int

    /// The day of the month, for a monthly or yearly repeat.
    public let dayOfMonth: Int

    /// The month, counted from one, for a yearly repeat.
    public let month: Int

    /// - Parameters:
    ///   - frequency: how often it comes round.
    ///   - hour: the hour of the day it is wanted at.
    ///   - minute: the minute of that hour.
    ///   - weekday: the day of the week, counted from Sunday.
    ///   - dayOfMonth: the day of the month.
    ///   - month: the month, counted from one.
    public init(
        frequency: HowOften,
        hour: Int,
        minute: Int,
        weekday: Int,
        dayOfMonth: Int,
        month: Int
    ) {
        self.frequency = frequency
        self.hour = hour
        self.minute = minute
        self.weekday = weekday
        self.dayOfMonth = dayOfMonth
        self.month = month
    }

    /// The next moment this comes round, or nothing where no moment could.
    ///
    /// Strictly after the instant given, which `nextDate(after:matching:)`
    /// guarantees on its own — the awkward case Android has to step past by
    /// hand is one this call does not have.
    ///
    /// Nil where the recurrence names something a calendar does not have — an
    /// hour of 24, a month of 13, a day of the month past the 28th. The shim
    /// reads that as a refusal rather than arming an alarm, because a repeat
    /// nobody can predict is worse than one that was declined.
    ///
    /// - Parameter instant: the moment to count from.
    /// - Returns: the next occurrence, or nil where there is none.
    public func nextAfter(_ instant: Date) -> Date? {
        guard satisfiable else { return nil }

        return Calendar.current.nextDate(
            after: instant,
            matching: fixes().asDateComponents(),
            matchingPolicy: .nextTime
        )
    }

    /// Whether every field names something a calendar has.
    ///
    /// **The day of the month stops at the 28th, which is a narrowing and is
    /// written down here because it is one.** Every month has a 28th and no
    /// month has a 31st; the two platforms' calendars disagree about what to do
    /// with a 31st — this one skips to a month that has one, and `java.time`
    /// raises rather than answer — and a repeat an operator cannot predict is
    /// worse than one this bridge declined to arm. Somebody wanting the end of
    /// the month is asking for something neither library offers and this does
    /// not pretend to.
    private var satisfiable: Bool {
        (0...Edge.lastHour).contains(hour)
            && (0...Edge.lastMinute).contains(minute)
            && (0...Edge.lastWeekday).contains(weekday)
            && (1...Edge.lastSafeDay).contains(dayOfMonth)
            && (1...Edge.lastMonth).contains(month)
    }

    /// The fields this frequency fixes, and no others.
    ///
    /// The whole of what a frequency means, in one place both platforms read:
    /// iOS hands them to a repeating trigger, Android walks a date onto them,
    /// and neither has to know what `weekly` implies.
    ///
    /// - Returns: the fields fixed, counted the way the wire counts them.
    public func fixes() -> WhatItFixes {
        switch frequency {
        case .hourly:
            return WhatItFixes(hour: nil, minute: minute, weekday: nil, dayOfMonth: nil, month: nil)
        case .daily:
            return WhatItFixes(hour: hour, minute: minute, weekday: nil, dayOfMonth: nil, month: nil)
        case .weekly:
            return WhatItFixes(hour: hour, minute: minute, weekday: weekday, dayOfMonth: nil, month: nil)
        case .monthly:
            return WhatItFixes(hour: hour, minute: minute, weekday: nil, dayOfMonth: dayOfMonth, month: nil)
        case .yearly:
            return WhatItFixes(hour: hour, minute: minute, weekday: nil, dayOfMonth: dayOfMonth, month: month)
        }
    }

    /// The edges of what a calendar will accept, named so a reader can check them.
    private enum Edge {
        /// The last hour of a day, counted from zero.
        static let lastHour = 23

        /// The last minute of an hour, counted from zero.
        static let lastMinute = 59

        /// The last day of a week, counted from Sunday as zero.
        static let lastWeekday = 6

        /// The last day of the month every month has.
        static let lastSafeDay = 28

        /// The last month of a year, counted from one.
        static let lastMonth = 12
    }
}

extension WhatItFixes {
    /// The same fields, in the numbering `Calendar` wants them in.
    ///
    /// The wire counts weekdays from Sunday as zero, because that is what both
    /// platforms' notification APIs do; `Calendar` counts from Sunday as one.
    /// Converted here rather than at each call site, where an off-by-one is a
    /// repeat arriving on the wrong day every week and nothing saying so.
    ///
    /// - Returns: the fields, as `Calendar` matches on them.
    public func asDateComponents() -> DateComponents {
        DateComponents(
            month: month,
            day: dayOfMonth,
            hour: hour,
            minute: minute,
            weekday: weekday.map { $0 + 1 }
        )
    }
}
