import Foundation
import Network

/// Whether there is a link, on iOS.
///
/// Namespace: `Lemonfiber.Link.*`
///
/// One question: can this device reach anything at all right now. The answer
/// lets the app tell *this phone has no network* from *that machine is not
/// answering*, which are two sentences with two different remedies and which a
/// socket timing out cannot tell apart.
///
/// The decision worth testing is not here. `LinkRule` holds it — including the
/// one that matters, that a platform which could not be asked is read as a link
/// that works — and it runs on a laptop with no device in sight.
///
/// **The kind of link is never read.** `NWPath` reports the interface type,
/// whether it is expensive and whether it is constrained. None of it is asked
/// for here and the envelope has no place to put it, so adding one means
/// explaining why rather than uncommenting a line.
///
/// **The monitor is started and stopped inside one call.** A monitor held for
/// the life of the process is a callback that outlives the question it was
/// asked for; this one is a snapshot, which is what the caller wanted.
///
/// **Nothing about the device reaches a log line.** Not an SSID, not an
/// interface name, not a carrier, not an address. The one line here carries the
/// outcome word, which is one of two.
/// What the monitor reported, held between its queue and the call that waits.
///
/// Written once on the monitor's own serial queue and read once the wait has
/// returned, which is after the write that signalled it. A `nil` here is the
/// monitor never having reported at all, which the rule reads as a platform
/// that could not be asked.
private final class WhatThePathSaid: @unchecked Sendable {
    var status: NWPath.Status?
}

enum LinkFunctions {
    /// What this plugin's log lines are tagged with.
    private static let tag = "Lemonfiber"

    /// How long to wait for the first path before giving up on the question.
    ///
    /// `NWPathMonitor` answers almost immediately and this is not a network
    /// timeout — it is the point at which *the platform did not answer* becomes
    /// the honest reading, which the rule then treats as a link that works.
    private static let longEnoughForAnAnswer: DispatchTime = .now() + .milliseconds(250)

    /// What the platform says about the link, as the rule's three facts.
    ///
    /// A monitor that never reports is answered as *could not be asked* rather
    /// than as *nothing is reachable*. A launch that cannot ask would otherwise
    /// land in a state whose remedy is *turn your wifi on*, which is wrong
    /// wherever the monitor is simply slow.
    private static func asKnown() -> LinkRule {
        let monitor = NWPathMonitor()
        let waiting = DispatchSemaphore(value: 0)
        let seen = WhatThePathSaid()

        monitor.pathUpdateHandler = { path in
            seen.status = path.status
            waiting.signal()
        }

        monitor.start(queue: DispatchQueue(label: "app.lemonfiber.link"))
        _ = waiting.wait(timeout: longEnoughForAnAnswer)
        monitor.cancel()

        guard let status = seen.status else {
            return LinkRule.unreadable
        }

        return LinkRule(
            linkWasReadable: true,
            hasAnActiveLink: status != .unsatisfied,
            carriesTraffic: status == .satisfied
        )
    }

    /// Answer whether anything is reachable from here.
    class Status: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let said = asKnown().said

            NSLog("%@ link: %@", tag, said.word)

            return Envelope.of(said.word).asAnswer()
        }
    }
}
