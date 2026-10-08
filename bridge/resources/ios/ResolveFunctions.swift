import Darwin
import Foundation

/// Look a machine's name up the way the phone itself does, on iOS.
///
/// Namespace: `Lemonfiber.Resolve`
///
/// The system resolver answers a `.local` name over multicast DNS, and this
/// asks it through `getaddrinfo`, the call every iOS networking API ends in.
/// What it found goes back for the app to send to while keeping the name.
///
/// **Never on the thread that called.** The look-up runs on a queue of its own,
/// and the call waits for it only so long: a name nothing answers for is
/// otherwise a wait of several seconds.
///
/// The decision worth testing is not here. `ResolveRule` holds it, and it runs
/// on a laptop with no device in sight.
///
/// **Nothing about the name or what it resolved to reaches a log line.** The one
/// line here carries the outcome word and how many addresses are usable.

/// What the resolver gave, carried across the queue boundary.
private final class WhatTheResolverGave: @unchecked Sendable {
    var found: [AnAddressFound] = []
}

enum ResolveFunctions {
    /// What this plugin's log lines are tagged with.
    private static let tag = "Lemonfiber"

    /// How long to wait for the resolver before giving up on the name.
    private static let longEnoughForAnAnswer: DispatchTimeInterval = .seconds(3)

    /// Every address `getaddrinfo` gives for one name, in its order.
    private static func lookedUp(_ host: String) -> [AnAddressFound] {
        var hints = addrinfo()
        hints.ai_family = AF_UNSPEC
        hints.ai_socktype = SOCK_STREAM

        var first: UnsafeMutablePointer<addrinfo>?
        guard getaddrinfo(host, nil, &hints, &first) == 0, let head = first else {
            return []
        }
        defer { freeaddrinfo(head) }

        var found: [AnAddressFound] = []
        var at: UnsafeMutablePointer<addrinfo>? = head

        while let entry = at {
            var written = [CChar](repeating: 0, count: Int(NI_MAXHOST))

            if getnameinfo(
                entry.pointee.ai_addr, entry.pointee.ai_addrlen, &written, socklen_t(written.count), nil, 0,
                NI_NUMERICHOST) == 0
            {
                let numeric = String(
                    decoding: written.prefix { $0 != 0 }.map { UInt8(bitPattern: $0) }, as: UTF8.self)
                let isVersion4 = entry.pointee.ai_family == AF_INET

                found.append(
                    AnAddressFound(
                        numeric, isVersion4: isVersion4,
                        needsAnInterface: !isVersion4 && numeric.contains("%")))
            }

            at = entry.pointee.ai_next
        }

        return found
    }

    /// What the system resolver says about one name, as the rule's facts.
    private static func asFound(host: String) -> ResolveRule {
        guard !host.isEmpty else {
            return ResolveRule.nothingFound
        }

        let waiting = DispatchSemaphore(value: 0)
        let gave = WhatTheResolverGave()

        DispatchQueue(label: "app.lemonfiber.resolve").async {
            gave.found = lookedUp(host)
            waiting.signal()
        }

        guard waiting.wait(timeout: .now() + longEnoughForAnAnswer) == .success else {
            return ResolveRule.nothingFound
        }

        return ResolveRule(gave.found)
    }

    /// Answer what one name resolves to.
    class Resolve: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let rule = asFound(host: parameters["host"] as? String ?? "")

            NSLog("%@ resolve: %@, %d usable", tag, rule.said.word, rule.usable.count)

            return Envelope.of(rule.said.word, carrying: ["addresses": rule.usable]).asAnswer()
        }
    }
}
