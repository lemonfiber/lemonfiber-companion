import Foundation
import UIKit
import UserNotifications

/// Telling somebody something, on iOS.
///
/// Namespace: `Lemonfiber.Telling.*`
///
/// Local notifications, never pushed. A pushed payload travels through Apple's
/// relay to reach this phone, which is a third party reading what a stack said
/// about somebody's home; a local notification is composed here, displayed
/// here, and never leaves the device.
///
/// The decisions worth testing are not in this file. `NotificationRule` holds
/// what the four facts a platform reports mean together, and `Recurrence` holds
/// when a repeat next comes round; both run on a laptop with no device in
/// sight. What is here is the part that cannot be tested off a phone: reading
/// the platform's facts, adding a request, taking one back.
///
/// **Everything here is synchronous over an asynchronous platform.** A bridge
/// function answers a value, and every `UNUserNotificationCenter` call answers
/// a callback, so each one is waited on. That is what lets `Standing` be a
/// reading rather than a subscription — and a reading is what `Ask` being a
/// separate function depends on.
///
/// **Nothing rendered is logged.** The title and the body are the only place a
/// stack's name could reach a log line, so the log carries the identifier the
/// caller chose and the outcome word and nothing else.
enum TellingFunctions {
    /// Whether the notification prompt has ever been raised by this application.
    private static let everAsked = "lemonfiber.telling.ever_asked"

    /// How long any one platform call is waited on before it is given up on.
    ///
    /// A bridge call that waited for ever would hold the thread it arrived on
    /// for ever. Every wait here is over a callback the system posts promptly,
    /// except the prompt itself — which is an operator reading a dialog, and is
    /// given two minutes.
    private static let patience: TimeInterval = 5

    /// How long the operator is given to answer the prompt.
    private static let patienceWithAPerson: TimeInterval = 120

    /// What the platform reports, gathered into the four facts the rule reads.
    ///
    /// iOS answers this question directly: `UNAuthorizationStatus` has a
    /// `.notDetermined` of its own, so nothing has to be reconstructed. The
    /// rule is asked anyway, with the same four facts, because a rule that
    /// exists on one platform only cannot be seen to disagree with the other.
    ///
    /// `everAsked` is read from this application's own record *or* from the
    /// platform having an answer at all. The record alone would miss somebody
    /// who refused before this version was installed; the platform alone is
    /// what Android cannot supply. Taking either is what makes the two halves
    /// answer the same way.
    private static func rule() -> NotificationRule {
        guard let status = authorizationStatus() else {
            // A centre this process could not read within five seconds. Read as
            // nobody having been asked, which is the safe answer in both
            // directions: it shows nothing, and it records no refusal that
            // nobody made.
            return NotificationRule.unasked
        }

        return NotificationRule(
            wouldAppear: [.authorized, .provisional, .ephemeral].contains(status),
            permissionIsAsked: true,
            wouldExplain: false,
            everAsked: UserDefaults.standard.bool(forKey: everAsked) || status != .notDetermined
        )
    }

    /// What the notification centre has been told, read rather than asked for.
    private static func authorizationStatus() -> UNAuthorizationStatus? {
        var answered: UNAuthorizationStatus?
        let waiting = DispatchSemaphore(value: 0)

        UNUserNotificationCenter.current().getNotificationSettings { settings in
            answered = settings.authorizationStatus
            waiting.signal()
        }

        _ = waiting.wait(timeout: .now() + patience)

        return answered
    }

    /// Hand one request to the centre, and say what became of it.
    private static func add(_ request: UNNotificationRequest, as outcome: String) -> [String: Any] {
        var refused: Error?
        let waiting = DispatchSemaphore(value: 0)

        UNUserNotificationCenter.current().add(request) { error in
            refused = error
            waiting.signal()
        }

        _ = waiting.wait(timeout: .now() + patience)

        if refused != nil {
            // The error's own message is dropped rather than logged. A platform
            // error carries what it failed on, and what it failed on here is
            // the rendered sentence.
            NSLog("%@", "Lemonfiber: telling \(request.identifier): the device refused it")

            return Envelope.refusing("withheld", because: "the_device_refused").asAnswer()
        }

        NSLog("%@", "Lemonfiber: telling \(request.identifier): \(outcome)")

        return Envelope.of(outcome).asAnswer()
    }

    /// What this bridge is willing to put in a notification, and nothing else.
    private static func content(_ said: WhatToSay) -> UNMutableNotificationContent {
        let content = UNMutableNotificationContent()

        content.title = said.title
        content.body = said.body

        return content
    }

    /// `Lemonfiber.Telling.Standing` — what the operator has already said.
    ///
    /// Reads and never prompts, which is the whole reason it is a separate
    /// function from `Ask`: an application that cannot read the standing answer
    /// without raising a dialog has no way to obey one, because reading becomes
    /// asking and somebody who already refused gets asked again.
    class Standing: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            BridgeResponse.success(data: Envelope.of(rule().said.word).asAnswer())
        }
    }

    /// `Lemonfiber.Telling.Ask` — raise the prompt, and record that it was raised.
    ///
    /// The recording is what keeps the two platforms answering alike. iOS does
    /// not need it — it reports `.notDetermined` itself — and Android cannot
    /// answer without it, so it is written on both and read on both.
    ///
    /// It waits for the operator rather than returning while the dialog is
    /// still on screen, so that the answer it gives is the answer they gave.
    class Ask: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard rule().mayAsk else {
                return BridgeResponse.success(data: Envelope.of(rule().said.word).asAnswer())
            }

            UserDefaults.standard.set(true, forKey: everAsked)

            let waiting = DispatchSemaphore(value: 0)

            let centre = UNUserNotificationCenter.current()

            centre.requestAuthorization(options: [.alert, .sound, .badge]) { _, _ in
                waiting.signal()
            }

            _ = waiting.wait(timeout: .now() + patienceWithAPerson)

            NSLog("%@", "Lemonfiber: telling: the prompt was raised")

            return BridgeResponse.success(data: Envelope.of(rule().said.word).asAnswer())
        }
    }

    /// `Lemonfiber.Telling.Show` — put something in front of the operator now.
    ///
    /// There is no `no_such_channel` here. Channels are Android's; iOS has one
    /// switch per application and the rule already reads it, so the outcome
    /// exists on the wire and is never the answer on this platform.
    class Show: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let said = WhatToSay.from(parameters) else {
                return BridgeResponse.error(
                    code: "INVALID_PARAMETERS",
                    message: "id, title and body are required"
                )
            }

            guard rule().mayShow else {
                return BridgeResponse.success(
                    data: Envelope.refusing("withheld", because: "not_permitted").asAnswer()
                )
            }

            return BridgeResponse.success(
                data: add(
                    UNNotificationRequest(identifier: said.id, content: content(said), trigger: nil),
                    as: "shown"
                )
            )
        }
    }

    /// `Lemonfiber.Telling.Schedule` — put something in front of them at a time.
    class Schedule: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let said = WhatToSay.from(parameters), let at = parameters["at"] as? Int else {
                return BridgeResponse.error(
                    code: "INVALID_PARAMETERS",
                    message: "id, title, body and at are required"
                )
            }

            let when = Date(timeIntervalSince1970: TimeInterval(at))

            guard rule().mayShow else {
                return BridgeResponse.success(
                    data: Envelope.refusing("withheld", because: "not_permitted").asAnswer()
                )
            }

            guard when > Date() else {
                return BridgeResponse.success(
                    data: Envelope.refusing("withheld", because: "the_time_has_passed").asAnswer()
                )
            }

            let fields = Calendar.current.dateComponents(
                [.year, .month, .day, .hour, .minute, .second],
                from: when
            )

            return BridgeResponse.success(
                data: add(
                    UNNotificationRequest(
                        identifier: said.id,
                        content: content(said),
                        trigger: UNCalendarNotificationTrigger(dateMatching: fields, repeats: false)
                    ),
                    as: "scheduled"
                )
            )
        }
    }

    /// `Lemonfiber.Telling.ScheduleRecurring` — the same, over and over.
    ///
    /// The platform repeats this one itself, which Android's alarms cannot do
    /// across a time-zone change — so there the bridge re-arms each occurrence
    /// by hand and here it hands the fields over once. The fields are the
    /// rule's, on both, which is what keeps the two from disagreeing about what
    /// `weekly` means.
    class ScheduleRecurring: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let said = WhatToSay.from(parameters) else {
                return BridgeResponse.error(
                    code: "INVALID_PARAMETERS",
                    message: "id, title and body are required"
                )
            }

            guard rule().mayShow else {
                return BridgeResponse.success(
                    data: Envelope.refusing("withheld", because: "not_permitted").asAnswer()
                )
            }

            guard let repeating = asked(parameters), repeating.nextAfter(Date()) != nil else {
                return BridgeResponse.success(
                    data: Envelope.refusing("withheld", because: "no_such_repeat").asAnswer()
                )
            }

            return BridgeResponse.success(
                data: add(
                    UNNotificationRequest(
                        identifier: said.id,
                        content: content(said),
                        trigger: UNCalendarNotificationTrigger(
                            dateMatching: repeating.fixes().asDateComponents(),
                            repeats: true
                        )
                    ),
                    as: "scheduled"
                )
            )
        }

        /// The recurrence the caller asked for, or nothing where it named none.
        private func asked(_ parameters: [String: Any]) -> Recurrence? {
            guard let frequency = HowOften.saying(parameters["frequency"] as? String) else {
                return nil
            }

            return Recurrence(
                frequency: frequency,
                hour: parameters["hour"] as? Int ?? 0,
                minute: parameters["minute"] as? Int ?? 0,
                weekday: parameters["weekday"] as? Int ?? 0,
                dayOfMonth: parameters["dayOfMonth"] as? Int ?? 1,
                month: parameters["month"] as? Int ?? 1
            )
        }
    }

    /// `Lemonfiber.Telling.Cancel` — take one back.
    ///
    /// Cancelling something that was never scheduled answers `cancelled`, for
    /// the reason forgetting a key that was never kept answers `forgotten`: it
    /// is the ordinary case after a refusal, and getting rid of something is
    /// the one operation that must always work.
    class Cancel: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let id = parameters["id"] as? String else {
                return BridgeResponse.error(code: "INVALID_PARAMETERS", message: "id is required")
            }

            let centre = UNUserNotificationCenter.current()

            centre.removePendingNotificationRequests(withIdentifiers: [id])
            centre.removeDeliveredNotifications(withIdentifiers: [id])

            return BridgeResponse.success(data: Envelope.of("cancelled").asAnswer())
        }
    }

    /// `Lemonfiber.Telling.CancelAll` — take back everything this application armed.
    class CancelAll: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let centre = UNUserNotificationCenter.current()

            centre.removeAllPendingNotificationRequests()
            centre.removeAllDeliveredNotifications()

            return BridgeResponse.success(data: Envelope.of("cancelled").asAnswer())
        }
    }

    /// `Lemonfiber.Telling.Pending` — what is still to come.
    ///
    /// Read from the platform, which keeps the list itself. Android's
    /// `AlarmManager` can be asked to arm one and cannot be asked what it
    /// holds, so the bridge keeps its own record there; the answer on the wire
    /// is the same either way, which is the point of the wire.
    class Pending: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            var identifiers: [String] = []
            let waiting = DispatchSemaphore(value: 0)

            UNUserNotificationCenter.current().getPendingNotificationRequests { requests in
                identifiers = requests.map(\.identifier)
                waiting.signal()
            }

            _ = waiting.wait(timeout: .now() + patience)

            return BridgeResponse.success(
                data: Envelope.of("read", carrying: ["pending": identifiers]).asAnswer()
            )
        }
    }

    /// `Lemonfiber.Telling.ClearBadge` — take the count off the home-screen icon.
    ///
    /// A real badge here, unlike Android, where what a launcher draws is a
    /// count of the notifications showing and clearing it means clearing those.
    class ClearBadge: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            if #available(iOS 16.0, *) {
                UNUserNotificationCenter.current().setBadgeCount(0)
            } else {
                DispatchQueue.main.async {
                    UIApplication.shared.applicationIconBadgeNumber = 0
                }
            }

            return BridgeResponse.success(data: Envelope.of("cleared").asAnswer())
        }
    }
}

/// What one notification says, as this bridge is willing to carry it.
///
/// Three fields and no more. The vendor's own takes a sound, a badge, a
/// subtitle, an arbitrary data payload and up to three action buttons; none of
/// that has a caller here, and every one of them is another place a value could
/// travel that the application never meant to send.
struct WhatToSay {
    /// What this notification is about, chosen by the caller and never rendered.
    let id: String

    /// The line the operator reads first.
    let title: String

    /// The line under it.
    let body: String

    /// The three fields, or a refusal naming all of them at once.
    ///
    /// One guard rather than three, because a caller handed "id is required"
    /// learns as much from "id, title and body are required" and the reading
    /// stays short enough to check at a glance.
    ///
    /// - Parameter parameters: what the bridge was handed.
    /// - Returns: what to say, or nil where any of the three is missing.
    static func from(_ parameters: [String: Any]) -> WhatToSay? {
        guard
            let id = parameters["id"] as? String,
            let title = parameters["title"] as? String,
            let body = parameters["body"] as? String
        else {
            return nil
        }

        return WhatToSay(id: id, title: title, body: body)
    }
}
