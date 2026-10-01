import Foundation
import Network

/// Whether the platform refuses this app the local network, on iOS.
///
/// Namespace: `Lemonfiber.LocalNetwork.*`
///
/// One question, asked of one address: would the platform let this app reach
/// it. iOS asks the operator before an app may reach the local network, and a
/// refusal looks exactly like a machine that is off. The path a connection
/// would take says which: `unsatisfiedReason` is `.localNetworkDenied` where it
/// was the permission.
///
/// The decision worth testing is not here. `LocalNetworkRule` holds it, and it
/// runs on a laptop with no device in sight.
///
/// **Nothing about the address reaches a log line.** The one line here carries
/// the outcome word, which is one of two.
///
/// **The connection is started and cancelled inside one call**, for
/// `LinkFunctions`' reason, and nothing is ever sent over it.

/// What the connection's path said, carried across the queue boundary.
private final class WhatThePathSaid: @unchecked Sendable {
    var readable = false
    var denied = false
}

enum LocalNetworkFunctions {
    /// What this plugin's log lines are tagged with.
    private static let tag = "Lemonfiber"

    /// How long to wait for the path before giving up on the question.
    private static let longEnoughForAnAnswer: DispatchTimeInterval = .seconds(1)

    /// What the platform says about the path to one address, as the rule's two facts.
    private static func asKnown(host: String, port: Int) -> LocalNetworkRule {
        guard let to = NWEndpoint.Port(rawValue: UInt16(clamping: port)), !host.isEmpty else {
            return LocalNetworkRule.unasked
        }

        let connection = NWConnection(host: NWEndpoint.Host(host), port: to, using: .tcp)
        let waiting = DispatchSemaphore(value: 0)
        let seen = WhatThePathSaid()

        connection.stateUpdateHandler = { state in
            switch state {
            case .ready:
                seen.readable = true
                waiting.signal()
            case .waiting, .failed:
                seen.readable = true
                seen.denied = connection.currentPath?.unsatisfiedReason == .localNetworkDenied
                waiting.signal()
            default:
                break
            }
        }

        connection.start(queue: DispatchQueue(label: "app.lemonfiber.localnetwork"))
        _ = waiting.wait(timeout: .now() + longEnoughForAnAnswer)
        connection.cancel()

        return LocalNetworkRule(pathWasReadable: seen.readable, platformDeniedIt: seen.denied)
    }

    /// Answer whether the platform refuses this app the way to one address.
    class Probe: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let host = parameters["host"] as? String ?? ""
            let port = parameters["port"] as? Int ?? 0
            let said = asKnown(host: host, port: port).said

            NSLog("%@ local network: %@", tag, said.word)

            return Envelope.of(said.word).asAnswer()
        }
    }
}
