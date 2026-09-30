import Foundation
import LocalAuthentication
import UIKit

/// The app lock, on iOS.
///
/// Namespace: `Lemonfiber.Lock.*`, and `Lemonfiber.Authenticate` through
/// `LemonfiberAuth`.
///
/// What the lock is decided by lives in `LockRule`, which imports nothing from
/// UIKit and is tested on a laptop. This file holds the one rule the process
/// has, feeds it the lifecycle and the device's answers, and asks
/// `LemonfiberFunctions` to keep the window covered while the rule says the
/// glass must show nothing.
///
/// **Only the platform's success callback opens the lock.** PHP reads whether
/// the lock stands through `Standing`, a bridge answer, and is woken by an event
/// that carries nothing: an event claiming the lock opened would be an event
/// anything able to send one could forge.
///
/// **Leaving is entering the background**, not resigning active: the Face ID
/// sheet, the passcode sheet, Control Centre and a glance at the app switcher
/// all resign active without the app having gone anywhere.
enum TheLock {
    /// The event that wakes PHP to read the lock again. It carries nothing.
    private static let moved = "Lemonfiber\\Native\\Events\\TheLockMoved"

    /// How long a call waits for the operator to answer the prompt.
    private static let patience: TimeInterval = 300

    /// Nanoseconds in a second, for the monotonic clock.
    private static let nanoseconds: UInt64 = 1_000_000_000

    /// Every read and write of `rule` holds this.
    private static let guarding = NSLock()

    /// The lock, for the life of the process. A new process is a cold start.
    nonisolated(unsafe) private static var rule: LockRule = .coldStart()

    /// The monotonic clock, in seconds.
    ///
    /// `CLOCK_MONOTONIC` keeps counting while the phone sleeps and cannot be set
    /// by the operator, so neither a sleeping phone nor a clock set back
    /// shortens a time away. `systemUptime` would stop while asleep.
    private static func now() -> Int64 {
        Int64(clock_gettime_nsec_np(CLOCK_MONOTONIC) / nanoseconds)
    }

    /// Whether the device has a screen lock to ask with.
    private static func canAsk() -> Bool {
        LAContext().canEvaluatePolicy(WhatUnlocks.accepted, error: nil)
    }

    /// Change the rule, and answer what it was before and what it is now.
    @discardableResult
    private static func change(_ how: (LockRule) -> LockRule) -> (was: LockRule, now: LockRule) {
        guarding.lock()
        defer { guarding.unlock() }

        let was = rule
        rule = how(rule)

        return (was, rule)
    }

    /// The rule as it stands.
    private static func current() -> LockRule {
        change { $0 }.now
    }

    /// Whether the glass must show nothing, which `LemonfiberFunctions` reads.
    static var mustCover: Bool {
        current().mustCover
    }

    /// Whether the lock stands right now.
    static func stands() -> Bool {
        current().standsAt(now: now(), canAsk: canAsk())
    }

    /// Start watching the app come and go. Called once from `LemonfiberInit`.
    static func install() {
        let centre = NotificationCenter.default

        centre.addObserver(
            forName: UIApplication.didEnterBackgroundNotification,
            object: nil,
            queue: .main
        ) { _ in
            change { $0.left(now()) }
            LemonfiberFunctions.refresh()
        }

        centre.addObserver(
            forName: UIApplication.willEnterForegroundNotification,
            object: nil,
            queue: .main
        ) { _ in
            let moved = change { $0.returned(now: now(), canAsk: canAsk()) }

            LemonfiberFunctions.refresh()

            if moved.now.held && !moved.was.held {
                wake()
            }
        }
    }

    /// Tell PHP to read the lock again.
    private static func wake() {
        DispatchQueue.main.async {
            LaravelBridge.shared.send?(moved, [:])
        }
    }

    /// Raise the device's own prompt, and hand its answer to `answer`.
    ///
    /// Refused, as a failure, where a prompt is already up — two prompts would
    /// be two answers to one question — and, `byItself`, where the rule does not
    /// allow asking unasked. A fresh `LAContext` per prompt, because a context
    /// remembers a success and could answer yes to a question nobody was asked.
    static func prompt(reason: String, byItself: Bool, answer: @escaping @Sendable (Bool) -> Void) {
        let moved = change { rule in
            if rule.prompting || (byItself && !rule.mayAskByItself) {
                return rule
            }

            return byItself ? rule.askingByItself() : rule.asking()
        }

        if moved.was.prompting || !moved.now.prompting {
            answer(false)

            return
        }

        LAContext().evaluatePolicy(WhatUnlocks.accepted, localizedReason: reason) { success, _ in
            change { $0.answered(succeeded: success) }
            LemonfiberFunctions.refresh()
            answer(success)
        }
    }

    /// Raise the prompt and wait for its answer on the calling thread.
    ///
    /// Never on the main thread, whose sheet it would be waiting for; a call
    /// arriving there answers no. The wait is bounded, and a prompt left up past
    /// it answers no here while the lock stays held.
    static func promptAndWait(reason: String) -> Bool {
        if Thread.isMainThread {
            return false
        }

        let waiting = DispatchSemaphore(value: 0)
        let answered = Answer()

        prompt(reason: reason, byItself: false) { success in
            answered.set(success)
            waiting.signal()
        }

        _ = waiting.wait(timeout: .now() + patience)

        return answered.get()
    }

    /// One answer handed from the prompt's queue to the waiting thread.
    private final class Answer: @unchecked Sendable {
        private let guarding = NSLock()
        private var value = false

        func set(_ new: Bool) {
            guarding.lock()
            value = new
            guarding.unlock()
        }

        func get() -> Bool {
            guarding.lock()
            defer { guarding.unlock() }

            return value
        }
    }

    /// `Lemonfiber.Lock.Standing` — whether the lock stands right now.
    class Standing: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            BridgeResponse.success(data: ["open": !stands()])
        }
    }

    /// `Lemonfiber.Lock.Waive` — the store holds nothing for the lock to guard.
    ///
    /// Asked by PHP only after reading the store and finding it empty.
    class Waive: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            change { $0.waived() }
            LemonfiberFunctions.refresh()

            return BridgeResponse.success(data: ["open": !stands()])
        }
    }

    /// `Lemonfiber.Lock.Drawn` — the lock screen is on the glass.
    ///
    /// The cover comes down two turns of the main queue later, once the frame
    /// PHP published has been drawn under it. Where `ask` is true and the rule
    /// allows it, the prompt goes up by itself.
    class Drawn: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            change { $0.drawn() }

            DispatchQueue.main.async {
                DispatchQueue.main.async { LemonfiberFunctions.refresh() }
            }

            if parameters["ask"] as? Bool == true, let reason = parameters["reason"] as? String {
                prompt(reason: reason, byItself: true) { success in
                    if success {
                        wake()
                    }
                }
            }

            return BridgeResponse.success(data: ["open": !stands()])
        }
    }
}
